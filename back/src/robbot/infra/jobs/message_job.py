"""
Job para processar mensagens recebidas/enviadas via WAHA.
"""

import asyncio
import json
import logging
import time
from typing import Any

from robbot.config.settings import settings
from robbot.domain.shared.enums import MessageDirection
from robbot.infra.db.session import get_sync_session
from robbot.infra.jobs.base_job import BaseJob, JobRetryableError
from robbot.infra.persistence.repositories.conversation_message_repository import (
    ConversationMessageRepository,
)
from robbot.infra.redis.client import get_redis_client

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


def process_debounced_message(chat_id: str) -> dict[str, Any]:
    """Process buffered messages for a chat as a single inbound message."""
    if not chat_id:
        return {"status": "skipped", "reason": "missing_chat_id"}

    redis_client = get_redis_client()
    buffer_key = f"waha:debounce:{chat_id}"
    job_key = f"waha:debounce:job:{chat_id}"

    raw = redis_client.get(buffer_key)
    if not raw:
        redis_client.delete(job_key)
        return {"status": "empty"}

    try:
        payload = json.loads(raw)
    except json.JSONDecodeError:
        redis_client.delete(buffer_key)
        redis_client.delete(job_key)
        return {"status": "error", "reason": "invalid_payload"}

    messages = [m for m in payload.get("messages", []) if isinstance(m, str) and m.strip()]
    if not messages:
        redis_client.delete(buffer_key)
        redis_client.delete(job_key)
        return {"status": "empty"}

    combined_text = "\n".join(messages).strip()
    last_payload = payload.get("last_payload", {})

    # CRITICAL FIX: Preserve original message metadata (type, _data, media, etc)
    # Instead of forcing type="text", use the last message's actual metadata

    _data_payload = last_payload.get("_data") or {}
    media_payload = last_payload.get("media") or {}

    # DEBUG: Log what we're preserving from debounce
    logger.info("[DEBOUNCE] Preservando metadata do last_payload:")
    logger.info("[DEBOUNCE]   type: %s", last_payload.get("type", "text"))
    logger.info("[DEBOUNCE]   _data.type: %s", _data_payload.get("type"))
    logger.info("[DEBOUNCE]   hasMedia: %s", last_payload.get("hasMedia", False))
    logger.info("[DEBOUNCE]   media keys: %s", list(media_payload.keys()))

    message_data = {
        "from": chat_id,
        "body": combined_text,
        "timestamp": int(time.time()),
        "session": last_payload.get("session", "default"),
        "type": last_payload.get("type", "text"),  # Preserve original type
        "_data": _data_payload,  # Preserve _data safely
        "media": media_payload,  # Preserve media safely
        "hasMedia": last_payload.get("hasMedia", False),  # Preserve hasMedia flag
        "debounced": True,
        "debounce_window": settings.MESSAGE_DEBOUNCE_SECONDS,
    }

    redis_client.delete(buffer_key)
    redis_client.delete(job_key)

    return process_message_job(message_data=message_data, message_direction="inbound")


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
            from robbot.services.bot.conversation_orchestrator import get_conversation_orchestrator

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

            # CRITICAL FIX: WAHA sends type="unknown" but _data.type has correct value (e.g., "ptt")
            _data = self.message_data.get("_data", {})
            message_type = self.message_data.get("type") or _data.get("type", "text")

            # DEBUG: Log message data recebido
            logger.info("[MESSAGE_JOB] Iniciando processamento:")
            logger.info("[MESSAGE_JOB]   message_data.type: %s", self.message_data.get("type"))
            logger.info("[MESSAGE_JOB]   _data.type: %s", _data.get("type"))
            logger.info("[MESSAGE_JOB]   hasMedia: %s", self.message_data.get("hasMedia"))
            logger.info("[MESSAGE_JOB]   message_type FINAL: %s", message_type)

            # Force "unknown" types to check _data.type (WAHA bug workaround)
            if message_type == "unknown" and _data.get("type"):
                message_type = _data.get("type")
                logger.info("[MESSAGE_JOB FIX] Corrigido type='unknown' para '%s' via _data.type", message_type)

            # Try multiple sources for media URL (WAHA can send in different formats)
            media_payload = self.message_data.get("media", {}) or _data

            # Try to get URL from multiple possible locations
            potential_url = (
                media_payload.get("url")
                or media_payload.get("mediaUrl")
                or media_payload.get("link")
                or self.message_data.get("mediaUrl")
                or self.message_data.get("url")
            )

            # DEBUG: Log detalhado de detecção de áudio
            logger.info(
                "[MESSAGE_JOB DEBUG] Tipo mensagem: %s | Media payload: %s | URL: %s | hasMedia: %s",
                message_type,
                "PRESENTE" if media_payload else "NULL",
                potential_url[:50] if potential_url else "NULL",
                self.message_data.get("hasMedia", False),
                extra={
                    "message_type": message_type,
                    "has_media_flag": self.message_data.get("hasMedia", False),
                    "media_payload_keys": list(media_payload.keys()) if media_payload else [],
                    "message_data_keys": list(self.message_data.keys()),
                    "potential_url_present": bool(potential_url),
                },
            )

            if message_type in ["voice", "ptt", "audio"]:
                has_audio = True
                audio_url = potential_url
                logger.info(
                    "[MESSAGE_JOB DEBUG] ÁUDIO DETECTADO! Type: %s | URL: %s",
                    message_type,
                    audio_url[:100] if audio_url else "NULL",
                )

                # If no URL found, log the entire payload for debugging
                if not audio_url:
                    logger.error(
                        "[MESSAGE_JOB ERROR] Mensagem de áudio sem URL! Payload completo: %s",
                        json.dumps(self.message_data, indent=2)[:1000],
                    )
            elif message_type == "video":
                has_video = True
                has_audio = True
                video_url = potential_url
                audio_url = potential_url
                logger.info(
                    "[MESSAGE_JOB DEBUG] VÍDEO DETECTADO! URL: %s",
                    video_url[:100] if video_url else "NULL",
                )

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
            from robbot.core.text_sanitizer import enforce_whatsapp_style

            with get_sync_session() as db:
                conv_msg_repo = ConversationMessageRepository(db)
                from robbot.infra.persistence.models.conversation_message_model import ConversationMessageModel

                content = self.message_data.get("text") or self.message_data.get("body")
                content = enforce_whatsapp_style(content)

                # Extract phone numbers
                chat_id = self.message_data.get("to") or self.message_data.get("chat_id", "")
                to_phone = chat_id.split("@")[0] if "@" in chat_id else chat_id
                from_phone = self.message_data.get("from", "BOT")

                message_record = ConversationMessageModel(
                    conversation_id=self.conversation_id,
                    direction=MessageDirection.OUTBOUND,
                    from_phone=from_phone,
                    to_phone=to_phone,
                    body=content,
                    waha_message_id=self.message_data.get("id"),
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
            except (ValueError, JobRetryableError) as e:
                logger.warning("Failed to process message in batch: %s", e)
                failed += 1

        return {
            "status": "completed",
            "processed": processed,
            "failed": failed,
            "total": len(self.messages),
        }
