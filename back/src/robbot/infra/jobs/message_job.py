"""
Job para processar mensagens recebidas/enviadas via WAHA.
"""

import asyncio
import logging
from typing import Any

from robbot.adapters.repositories.conversation_message_repository import (
    ConversationMessageRepository,
)
from robbot.infra.db.session import get_sync_session
from robbot.infra.jobs.base_job import BaseJob, JobRetryableError

logger = logging.getLogger(__name__)


def process_message_job(
    message_data: dict[str, Any],
    message_direction: str = "inbound",
    conversation_id: str | None = None,
    user_id: str | None = None,
    **kwargs,
) -> dict[str, Any]:
    """
    Module-level function for RQ to import and execute message processing jobs.
    This is the most reliable entry point to avoid 'ValueError: Invalid attribute name'.
    """
    job = MessageProcessingJob(
        message_data=message_data,
        message_direction=message_direction,
        conversation_id=conversation_id,
        user_id=user_id,
        **kwargs,
    )
    return job.run()


class MessageProcessingJob(BaseJob):
    """
    Job para processar mensagens WhatsApp (entrada/saída).

    Responsabilidades:
    - Validar formato da mensagem
    - Persistir em BD (messages, message_media)
    - Aplicar anti-ban delays
    - Enfileirar para IA se necessário
    - Atualizar status de conversação
    """

    def __init__(
        self,
        message_data: dict[str, Any],
        message_direction: str = "inbound",
        conversation_id: str | None = None,
        user_id: str | None = None,
        **kwargs,
    ):
        """
        Inicializar job de mensagem.
        """
        # Filter out RQ-specific kwargs that BaseJob doesn't accept
        base_job_kwargs = {}
        if "job_id" in kwargs:
            base_job_kwargs["job_id"] = kwargs["job_id"]
        if "attempt" in kwargs:
            base_job_kwargs["attempt"] = kwargs["attempt"]
        if "metadata" in kwargs:
            base_job_kwargs["metadata"] = kwargs["metadata"]

        super().__init__(**base_job_kwargs)

        self.message_data = message_data
        self.message_direction = message_direction
        self.conversation_id = conversation_id
        self.user_id = user_id

        # Validação inicial
        self._validate_message_data()

    @staticmethod
    def run_job(
        message_data: dict[str, Any],
        message_direction: str = "inbound",
        conversation_id: str | None = None,
        user_id: str | None = None,
        **kwargs,
    ) -> dict[str, Any]:
        """Run job for RQ (fallback static method)."""
        return process_message_job(
            message_data=message_data,
            message_direction=message_direction,
            conversation_id=conversation_id,
            user_id=user_id,
            **kwargs,
        )

    def _validate_message_data(self) -> None:
        """Validar formato básico da mensagem."""
        if ("from" not in self.message_data or not self.message_data["from"]) and (
            "phone" not in self.message_data or not self.message_data["phone"]
        ):
            raise ValueError("Campo obrigatório ausente: from/phone")

    def execute(self) -> dict[str, Any]:
        """
        Executar processamento da mensagem.
        """
        logger.info(
            "Processando mensagem %s para %s",
            self.message_direction,
            self.message_data.get("phone") or self.message_data.get("from"),
            extra=self._log_context(),
        )

        if self.message_direction == "inbound":
            return self._process_inbound_message()
        else:
            return self._persist_outbound_message()

    def _process_inbound_message(self) -> dict[str, Any]:
        """
        Processar mensagem inbound com ConversationOrchestrator.
        """
        try:
            from robbot.services.conversation_orchestrator import get_conversation_orchestrator

            orchestrator = get_conversation_orchestrator()

            # Extrair dados da mensagem
            chat_id = self.message_data.get("from", "")
            phone = chat_id.split("@")[0] if "@" in chat_id else chat_id
            text = self.message_data.get("body", "")

            # Detectar mídia
            has_audio = False
            audio_url = None
            has_video = False
            video_url = None
            message_type = self.message_data.get("type", "text")

            media_payload = self.message_data.get("media", {}) or self.message_data.get("_data", {})
            potential_url = media_payload.get("url")

            if message_type in ["voice", "ptt", "audio"]:
                has_audio = True
                audio_url = potential_url
            elif message_type == "video":
                has_video = True
                has_audio = True
                video_url = potential_url
                audio_url = potential_url

            # Processar com orchestrator
            result = asyncio.run(
                orchestrator.process_inbound_message(
                    chat_id=chat_id,
                    phone_number=phone,
                    message_text=text,
                    session_name=self.message_data.get("session", "default"),
                    has_audio=has_audio,
                    audio_url=audio_url,
                    has_video=has_video,
                    video_url=video_url,
                )
            )

            logger.info(
                "[SUCCESS] Mensagem processada (conv_id=%s)",
                result.get("conversation_id"),
                extra=self._log_context(),
            )

            return {
                "status": "processed",
                "conversation_id": result.get("conversation_id"),
                "response_sent": result.get("response_sent"),
                "intent": result.get("intent"),
                "maturity_score": result.get("maturity_score"),
            }

        except Exception as e:
            logger.error("[ERROR] Erro no Orchestrator: %s", e, exc_info=True)
            raise JobRetryableError(f"Failed to process message: {e}") from e

    def _persist_outbound_message(self) -> dict[str, Any]:
        """Persistir mensagem outbound."""
        try:
            with get_sync_session() as db:
                conv_msg_repo = ConversationMessageRepository(db)
                from robbot.infra.db.models import MessageModel

                message_record = MessageModel(
                    conversation_id=self.conversation_id,
                    direction=self.message_direction,
                    content=self.message_data.get("text") or self.message_data.get("body"),
                    message_type=self.message_data.get("type", "text"),
                    waha_message_id=self.message_data.get("id"),
                    phone=self.message_data.get("phone"),
                    timestamp=self.message_data.get("timestamp"),
                    metadata=self.message_data,
                )
                conv_msg_repo.create(message_record)

                return {
                    "status": "success",
                    "message_id": message_record.id,
                    "conversation_id": self.conversation_id,
                }
        except Exception as e:
            raise JobRetryableError(f"Erro ao persistir outbound: {e}") from e


class MessageBatchProcessingJob(BaseJob):
    """
    Job para processar lote de mensagens.
    """

    def __init__(
        self,
        messages: list[dict[str, Any]],
        conversation_id: str | None = None,
        **kwargs,
    ):
        super().__init__(**kwargs)
        self.messages = messages
        self.conversation_id = conversation_id

    def execute(self) -> dict[str, Any]:
        processed = 0
        failed = 0

        for _idx, msg_data in enumerate(self.messages):
            try:
                job = MessageProcessingJob(
                    message_data=msg_data,
                    conversation_id=self.conversation_id,
                    attempt=0,
                )
                job.run()
                processed += 1
            except Exception:
                failed += 1

        return {
            "status": "completed",
            "processed": processed,
            "failed": failed,
            "total": len(self.messages),
        }
