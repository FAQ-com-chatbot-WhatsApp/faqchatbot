"""Worker dedicado para executar polling periódico de mensagens WAHA."""

import logging
import time
from datetime import UTC, datetime

from rq.job import Job, JobStatus

from robbot.config.settings import get_settings
from robbot.core.logging_setup import configure_logging
from robbot.infra.jobs.message_polling_job import poll_waha_messages
from robbot.infra.redis.client import get_redis_client
from robbot.services.queue_service import get_queue_service

configure_logging()
logger = logging.getLogger(__name__)
settings = get_settings()


def run_polling_worker():
    """
    Executa polling de mensagens WAHA a cada intervalo configurado.

    SUBSTITUIÇÃO DE WEBHOOKS:
    - Webhooks do WEBJS não funcionam de forma confiável
    - Polling garante 100% de captura de mensagens
    - Intervalo padrão: 10 segundos (configurável via env)
    """
    queue_service = get_queue_service()
    polling_interval = getattr(settings, "WAHA_POLLING_INTERVAL", 10)

    logger.info(
        "=== WAHA POLLING WORKER INICIADO ===",
        extra={
            "interval_seconds": polling_interval,
            "dev_mode": settings.DEV_MODE,
            "dev_phones": ", ".join(settings.dev_phone_list) if settings.DEV_MODE else "ALL",
        },
    )

    if settings.DEV_MODE and settings.dev_phone_list:
        logger.info(
            "[DEV MODE] Monitorando %d números: %s", len(settings.dev_phone_list), ", ".join(settings.dev_phone_list)
        )
    elif settings.DEV_MODE:
        logger.warning("[DEV MODE] ATIVO mas sem números configurados - ignorando todas as mensagens")
    else:
        logger.info("[PROD MODE] Monitorando TODOS os números")

    consecutive_failures = 0
    max_failures = 5

    while True:
        try:
            now = datetime.now(UTC)

            # Enfileirar job de polling
            job_id_val = queue_service.enqueue_custom(
                func=poll_waha_messages,
                queue_name="messages",
                job_id=f"waha-polling-{int(now.timestamp())}",
                timeout=60,
            )

            logger.info(
                "[POLLING WORKER] Job de polling enfileirado: %s",
                job_id_val,
                extra={"job_id": job_id_val, "queue": "messages"},
            )

            # Buscar objeto Job para monitoramento
            redis_conn = get_redis_client()
            job = Job.fetch(job_id_val, connection=redis_conn)

            # Aguardar conclusão do job (com timeout)
            timeout = 45  # 30s job + 15s buffer
            start_time = time.time()

            while time.time() - start_time < timeout:
                job.refresh()

                if job.get_status() == JobStatus.FINISHED:
                    result = job.result
                    logger.info("[POLLING WORKER] Polling concluído: %s", result, extra={"result": result})
                    consecutive_failures = 0
                    break

                if job.get_status() == JobStatus.FAILED:
                    logger.error("[POLLING WORKER] Polling falhou: %s", job.exc_info, extra={"error": job.exc_info})
                    consecutive_failures += 1
                    break

                time.sleep(1)

            # Verificar falhas consecutivas
            if consecutive_failures >= max_failures:
                logger.critical(
                    "[POLLING WORKER] %d falhas consecutivas - PAUSANDO por 60s",
                    consecutive_failures,
                    extra={"failures": consecutive_failures},
                )
                time.sleep(60)
                consecutive_failures = 0

            # Aguardar próximo intervalo
            time.sleep(polling_interval)

        except KeyboardInterrupt:
            logger.info("[POLLING WORKER] Interrompido pelo usuário")
            break

        except Exception as e:  # pylint: disable=broad-except
            logger.error("[POLLING WORKER] Erro inesperado: %s", e, extra={"error": str(e)}, exc_info=True)
            consecutive_failures += 1
            time.sleep(polling_interval)


if __name__ == "__main__":
    run_polling_worker()
