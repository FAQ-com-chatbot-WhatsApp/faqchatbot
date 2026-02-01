"""
Service para orquestração de filas (jobs assíncronos).
"""

import logging
from typing import Any

from rq.exceptions import NoSuchJobError
from rq.job import Job
from rq.registry import FailedJobRegistry

from robbot.config.settings import settings
from robbot.core.custom_exceptions import QueueError
from robbot.infra.jobs.escalation_job import EscalationJob, process_escalation_job
from robbot.infra.jobs.gemini_job import GeminiAIProcessingJob, process_gemini_job
from robbot.infra.jobs.message_job import MessageProcessingJob, process_message_job
from robbot.infra.jobs.scheduler_job import ScheduledJob
from robbot.infra.redis.queue import get_queue_manager

logger = logging.getLogger(__name__)


class QueueService:
    """
    Service para gerenciar jobs assíncronos.

    Responsabilidades:
    - Enfileirar jobs em fila apropriada
    - Monitorar progresso
    - Recuperar resultados
    - Limpeza de jobs antigos
    - Métricas e logging
    """

    def __init__(self):
        """Inicializar serviço de filas."""
        self.queue_manager = get_queue_manager()
        logger.info("[SUCCESS] QueueService inicializado")

    # =====================================================================
    # ENFILEIRAR JOBS
    # =====================================================================

    def enqueue_message_processing(
        self,
        message_data: dict[str, Any],
        conversation_id: str | None = None,
        message_direction: str = "inbound",
    ) -> str:
        """
        Enfileirar mensagem para processamento.

        Args:
            message_data: Payload da mensagem
            conversation_id: ID da conversa (se conhecida)
            message_direction: "inbound" ou "outbound"

        Returns:
            Job ID para rastreamento
        """
        job = MessageProcessingJob(
            message_data=message_data,
            conversation_id=conversation_id,
            message_direction=message_direction,
            attempt=0,
        )

        enqueued_job = self.queue_manager.queue_messages.enqueue(
            process_message_job,
            message_data=message_data,
            message_direction=message_direction,
            conversation_id=conversation_id,
            user_id=None,  # or pass if needed
            job_id=job.job_id,
            timeout=600,  # 10 minutes explicit timeout
            result_ttl=settings.RQ_DEFAULT_RESULT_TTL,
            failure_ttl=settings.RQ_DEFAULT_FAILURE_TTL,
        )

        # Force timeout in Redis
        try:
            enqueued_job.timeout = 600  # 10 minutes
            enqueued_job.save()
            logger.debug("Job %s timeout set to 600s in Redis", job.job_id)
        except Exception as e:  # noqa: BLE001
            logger.warning("Failed to set job timeout in Redis: %s", e)

        logger.info(
            "Mensagem enfileirada (fila: messages, timeout: %ss) -> %s",
            settings.RQ_JOB_TIMEOUT_MESSAGE,
            job.job_id,
            extra={
                "job_id": job.job_id,
                "queue": "messages",
                "timeout": settings.RQ_JOB_TIMEOUT_MESSAGE,
                "phone": message_data.get("phone"),
            },
        )

        return job.job_id

    def enqueue_ai_processing(
        self,
        conversation_id: str,
        message_id: str,
        user_input: str,
        phone: str,
    ) -> str:
        """
        Enfileirar mensagem para processamento com IA.

        Args:
            conversation_id: ID da conversa
            message_id: ID da mensagem
            user_input: Texto a processar
            phone: Telefone do usuário

        Returns:
            Job ID
        """
        job = GeminiAIProcessingJob(
            conversation_id=conversation_id,
            message_id=message_id,
            user_input=user_input,
            phone=phone,
            attempt=0,
        )

        self.queue_manager.queue_ai.enqueue(
            process_gemini_job,
            conversation_id=conversation_id,
            message_id=message_id,
            user_input=user_input,
            phone=phone,
            job_id=job.job_id,
            result_ttl=settings.RQ_DEFAULT_RESULT_TTL,
            failure_ttl=settings.RQ_DEFAULT_FAILURE_TTL,
        )

        logger.info(
            "[INFO] AI job queued (queue: ai) -> %s",
            job.job_id,
            extra={
                "job_id": job.job_id,
                "queue": "ai",
                "conversation_id": conversation_id,
            },
        )

        return job.job_id

    def enqueue_escalation(
        self,
        conversation_id: str,
        reason: str,
        phone: str,
        user_name: str | None = None,
    ) -> str:
        """
        Enfileirar escalação para secretária.

        Args:
            conversation_id: ID da conversa
            reason: Motivo da escalação
            phone: Telefone do usuário
            user_name: Nome do usuário

        Returns:
            Job ID
        """
        job = EscalationJob(
            conversation_id=conversation_id,
            reason=reason,
            phone=phone,
            user_name=user_name,
            attempt=0,
        )

        self.queue_manager.queue_escalation.enqueue(
            process_escalation_job,
            conversation_id=conversation_id,
            reason=reason,
            phone=phone,
            user_name=user_name,
            job_id=job.job_id,
            result_ttl=settings.RQ_DEFAULT_RESULT_TTL,
            failure_ttl=settings.RQ_DEFAULT_FAILURE_TTL,
        )

        logger.info(
            "Escalação enfileirada (fila: escalation) -> %s",
            job.job_id,
            extra={
                "job_id": job.job_id,
                "queue": "escalation",
                "conversation_id": conversation_id,
                "reason": reason,
            },
        )

        return job.job_id

    def enqueue_scheduled_job(
        self,
        scheduled_job: ScheduledJob,
    ) -> str:
        """
        Enfileirar job agendado.

        Args:
            scheduled_job: Instância de ScheduledJob

        Returns:
            Job ID
        """
        # Escolher fila por tipo
        queue_name = "escalation"  # Default

        self.queue_manager.get_queue(queue_name).enqueue_at(
            scheduled_job.scheduled_for,
            scheduled_job.run,
            job_id=scheduled_job.job_id,
            result_ttl=settings.RQ_DEFAULT_RESULT_TTL,
            failure_ttl=settings.RQ_DEFAULT_FAILURE_TTL,
        )

        logger.info(
            "Job scheduled: %s (executa em %s)",
            scheduled_job.job_id,
            scheduled_job.scheduled_for,
            extra={
                "job_id": scheduled_job.job_id,
                "scheduled_for": scheduled_job.scheduled_for.isoformat(),
                "task_type": scheduled_job.task_type,
            },
        )

        return scheduled_job.job_id

    def enqueue_custom(
        self,
        func: Any,
        queue_name: str = "messages",
        job_id: str | None = None,
        timeout: int | None = None,
    ) -> str:
        """
        Enfileirar job customizado (sem argumentos).

        Args:
            func: Função a executar (sem argumentos)
            queue_name: Nome da fila
            job_id: ID customizado do job

        Returns:
            Job ID
        """
        queue = self.queue_manager.get_queue(queue_name)
        
        # Enqueue simples - permite timeout customizado
        enqueue_kwargs: dict[str, Any] = {
            "job_id": job_id,
            "result_ttl": settings.RQ_DEFAULT_RESULT_TTL,
            "failure_ttl": settings.RQ_DEFAULT_FAILURE_TTL,
        }
        if timeout is not None:
            enqueue_kwargs["timeout"] = timeout

        enqueued_job = queue.enqueue(func, **enqueue_kwargs)
        
        logger.info(
            "Job customizado enfileirado (fila: %s) -> %s",
            queue_name,
            enqueued_job.id,
            extra={
                "job_id": enqueued_job.id,
                "queue": queue_name,
                "function": func.__name__,
            },
        )
        
        return enqueued_job.id

    # =====================================================================
    # MONITORAR JOBS
    # =====================================================================

    def get_job_status(self, job_id: str) -> dict[str, Any]:
        """
        Obter status de um job.

        Args:
            job_id: ID do job

        Returns:
            Dict com status, resultado, erros
        """
        # Procurar em todas as filas
        for queue_name, queue in self.queue_manager.get_all_queues().items():
            try:
                rq_job = Job.fetch(job_id, connection=queue.connection)

                return {
                    "job_id": job_id,
                    "queue": queue_name,
                    "status": rq_job.get_status(),
                    "is_started": rq_job.is_started,
                    "is_finished": rq_job.is_finished,
                    "is_failed": rq_job.is_failed,
                    "result": rq_job.result,
                    "exc_info": rq_job.exc_info,
                    "created_at": rq_job.created_at.isoformat() if rq_job.created_at else None,
                    "started_at": rq_job.started_at.isoformat() if rq_job.started_at else None,
                    "ended_at": rq_job.ended_at.isoformat() if rq_job.ended_at else None,
                }
            except (QueueError, ValueError):
                # Job inválido ou corrompido - pular
                continue

        return {
            "job_id": job_id,
            "status": "not_found",
            "error": f"Job {job_id} não encontrado",
        }

    def get_queue_stats(self) -> dict[str, Any]:
        """
        Obter estatísticas de todas as filas.

        Returns:
            Dict com contagem, workers, failed jobs
        """
        return {
            "timestamp": "2025-12-12T00:00:00Z",
            "queues": self.queue_manager.get_queue_stats(),
        }

    def get_failed_jobs(self, limit: int = 10) -> list[dict[str, Any]]:
        """
        Obter jobs falhados (DLQ).

        Args:
            limit: Número máximo de jobs a retornar

        Returns:
            Lista de jobs falhados com detalhes
        """
        failed_jobs = []
        queue = self.queue_manager.queue_failed
        failed_registry = FailedJobRegistry(queue=queue, connection=queue.connection)

        for job_id in list(failed_registry.get_job_ids())[:limit]:
            try:
                job = Job.fetch(job_id, connection=queue.connection)
                failed_jobs.append(
                    {
                        "job_id": job_id,
                        "type": job.func_name or "unknown",
                        "failed_at": job.ended_at.isoformat() if job.ended_at else None,
                        "error": job.exc_info,
                    }
                )
            except (QueueError, ValueError):
                # Job inválido - pular
                continue

        return failed_jobs

    # =====================================================================
    # GERENCIAR JOBS
    # =====================================================================

    def retry_job(self, job_id: str) -> bool:
        """
        Retryar job falhado.

        Args:
            job_id: ID do job

        Returns:
            True se conseguiu enfileirar novamente
        """
        try:
            # Search for job in any queue
            for queue_name, queue in self.queue_manager.get_all_queues().items():
                try:
                    job = Job.fetch(job_id, connection=queue.connection)

                    # Requeue o job (RQ automaticamente coloca na fila certa)
                    job.requeue()

                    logger.info(
                        "Job %s reenfileirado para retry",
                        job_id,
                        extra={"job_id": job_id, "queue": queue_name},
                    )
                    return True

                except (QueueError, ValueError, NoSuchJobError):
                    # Queue não existe ou job inválido
                    continue

            logger.warning("[WARNING] Job %s not found for retry", job_id)
            return False

        except QueueError:
            raise
        except Exception as e:  # noqa: BLE001 (blind exception)
            logger.error("[ERROR] Failed to retry job %s: %s", job_id, e)
            raise QueueError(f"Failed to retry job {job_id}: {e}") from e

    def retry_all_failed(self) -> int:
        """
        Retryar todos os jobs falhados.

        Returns:
            Número de jobs retentados
        """
        retried = 0
        queue = self.queue_manager.queue_failed
        failed_registry = FailedJobRegistry(queue=queue, connection=queue.connection)

        for job_id in list(failed_registry.get_job_ids()):
            if self.retry_job(job_id):
                retried += 1

        logger.info("[INFO] %s failed jobs re-queued", retried)
        return retried

    def clear_failed_queue(self) -> int:
        """
        Limpar todos os jobs falhados (DLQ).

        [WARNING] OPERAÇÃO IRREVERSÍVEL!

        Returns:
            Número de jobs removidos
        """
        queue = self.queue_manager.queue_failed
        failed_registry = FailedJobRegistry(queue=queue, connection=queue.connection)
        job_ids = list(failed_registry.get_job_ids())
        count = len(job_ids)

        # Remover todos os jobs falhados
        for job_id in job_ids:
            try:
                job = Job.fetch(job_id, connection=queue.connection)
                job.delete()
            except (QueueError, ValueError) as e:
                logger.warning("[WARNING] Failed to delete job %s: %s", job_id, e)

        logger.warning("[WARNING] Dead Letter Queue cleaned: %s jobs removed", count)
        return count

    def cancel_job(self, job_id: str) -> bool:
        """
        Cancelar job.

        Args:
            job_id: ID do job

        Returns:
            True se conseguiu cancelar
        """
        try:
            # Procurar em todas as filas
            for queue in self.queue_manager.get_all_queues().values():
                try:
                    job = Job.fetch(job_id, connection=queue.connection)
                    job.cancel()
                    logger.info("[INFO] Job %s cancelled", job_id)
                    return True
                except (QueueError, ValueError):
                    # Job não existe nesta fila
                    continue

            return False

        except QueueError:
            raise
        except Exception as e:  # noqa: BLE001 (blind exception)
            logger.error("[ERROR] Failed to cancel job %s: %s", job_id, e)
            raise QueueError(f"Failed to cancel job {job_id}: {e}") from e

    # =====================================================================
    # HEALTH CHECK
    # =====================================================================

    def health_check(self) -> dict[str, Any]:
        """
        Verificar saúde do sistema de filas.

        Returns:
            Dict com status de cada componente
        """
        return {
            "status": "healthy",
            "queues": self.queue_manager.health_check(),
            "queue_manager": "ok",
        }


# Singleton
_queue_service: QueueService | None = None


def get_queue_service() -> QueueService:
    """Obter instância singleton de QueueService."""
    global _queue_service  # pylint: disable=global-statement

    if _queue_service is None:
        _queue_service = QueueService()

    return _queue_service
