"""
Job para processar mensagens recebidas/enviadas via WAHA.
"""

import logging
from typing import Any

from robbot.adapters.repositories.conversation_message_repository import (
    ConversationMessageRepository,
)
from robbot.infra.db.session import get_sync_session
from robbot.infra.jobs.base_job import BaseJob, JobRetryableError

logger = logging.getLogger(__name__)
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
        message_direction: str = "inbound",  # "inbound" ou "outbound"
        conversation_id: str | None = None,
        user_id: str | None = None,
        **kwargs,
    ):
        """
        Inicializar job de mensagem.

        Args:
            message_data: Payload da mensagem (conteúdo, tipo, timestamp, etc)
            message_direction: "inbound" ou "outbound"
            conversation_id: ID da conversa
            user_id: ID do usuário proprietário
            **kwargs: Argumentos herdados de BaseJob
        """
        super().__init__(**kwargs)

        self.message_data = message_data
        self.message_direction = message_direction
        self.conversation_id = conversation_id
        self.user_id = user_id

        # Validação inicial
        self._validate_message_data()

    def _validate_message_data(self) -> None:
        """Validar formato básico da mensagem."""
        required_fields = ["phone", "text"]

        for field in required_fields:
            if field not in self.message_data or not self.message_data[field]:
                raise ValueError(f"Campo obrigatório ausente: {field}")

    def execute(self) -> dict[str, Any]:
        """
        Executar processamento da mensagem.

        Returns:
            Dict com ID da mensagem e resultado do processamento

        Raises:
            JobRetryableError: Se BD indisponível (aguardará retry)
            ValueError: Se payload inválido (falha imediata)
        """
        logger.info(
            "Processando mensagem %s para %s",
            self.message_direction,
            self.message_data.get('phone'),
            extra=self._log_context(),
        )

        # Se for mensagem inbound, processar com orchestrator
        if self.message_direction == "inbound":
            return self._process_inbound_message()
        else:
            # Mensagens outbound apenas persistir (já foram enviadas)
            return self._persist_outbound_message()

    def _process_inbound_message(self) -> dict[str, Any]:
        """
        Processar mensagem inbound com ConversationOrchestrator.

        Detecta automaticamente áudio e transcreve antes de processar.
        """
        from robbot.services.conversation_orchestrator import get_conversation_orchestrator

        try:
            orchestrator = get_conversation_orchestrator()

            # Extrair dados da mensagem
            chat_id = self.message_data.get("from", "")
            phone = chat_id.split("@")[0] if "@" in chat_id else chat_id
            text = self.message_data.get("body", "")

            # Detectar se é mensagem de áudio ou vídeo
            has_audio = False
            audio_url = None
            has_video = False
            video_url = None
            message_type = self.message_data.get("type", "")

            if message_type in ["voice", "ptt", "audio"]:
                has_audio = True
                # WAHA fornece URL do áudio no campo media ou _data
                audio_url = self.message_data.get("media", {}).get("url") or self.message_data.get("_data", {}).get("url")

                if not audio_url:
                    logger.warning("[WARNING] Mensagem de áudio sem URL (type=%s)", message_type)
                else:
                    logger.info("[INFO] Audio detected: %s", audio_url)

            elif message_type == "video":
                has_video = True
                has_audio = True  # Vídeo também tem áudio para transcrever
                video_url = self.message_data.get("media", {}).get("url") or self.message_data.get("_data", {}).get("url")
                audio_url = video_url  # Mesmo URL (extrairemos áudio)

                if not video_url:
                    logger.warning("[WARNING] Mensagem de vídeo sem URL")
                else:
                    logger.info("[INFO] Video detected: %s", video_url)

            # Processar com orchestrator (fluxo completo)
            # Isso vai:
            # 1. Criar/buscar conversa
            # 2. Transcrever áudio/vídeo (se houver)
            # 3. Gerar descrição visual de vídeo (se houver)
            # 4. Salvar mensagem
            # 5. Buscar contexto ChromaDB
            # 6. Detectar intenção
            # 7. Gerar resposta
            # 8. Atualizar score
            # 9. Enviar via WAHA
            # 10. Salvar resposta
            import asyncio
            result = asyncio.run(orchestrator.process_inbound_message(
                chat_id=chat_id,
                phone_number=phone,
                message_text=text,
                session_name=self.message_data.get("session", "default"),
                has_audio=has_audio,
                audio_url=audio_url,
                has_video=has_video,
                video_url=video_url,
            ))

            logger.info(
                "[SUCCESS] Mensagem processada com orchestrator (conv_id=%s)",
                result['conversation_id'],
                extra=self._log_context(),
            )

            return {
                "status": "processed",
                "conversation_id": result["conversation_id"],
                "response_sent": result["response_sent"],
                "intent": result["intent"],
                "maturity_score": result["maturity_score"],
            }

        except Exception as e:  # noqa: BLE001 (blind exception)
            logger.error(
                "[ERROR] Erro ao processar com orchestrator: %s",
                e,
                extra=self._log_context(),
                exc_info=True,
            )
            raise JobRetryableError(f"Failed to process message: {e}") from e

    def _persist_outbound_message(self) -> dict[str, Any]:
        """Persistir mensagem outbound (apenas registro)."""
        try:
            with get_sync_session() as db:
                conv_msg_repo = ConversationMessageRepository(db)

                from robbot.infra.db.models import MessageModel
                message_record = MessageModel(
                    conversation_id=self.conversation_id,
                    direction=self.message_direction,
                    content=self.message_data.get("text"),
                    message_type=self.message_data.get("type", "text"),
                    waha_message_id=self.message_data.get("id"),
                    phone=self.message_data.get("phone"),
                    timestamp=self.message_data.get("timestamp"),
                    metadata=self.message_data,
                )
                conv_msg_repo.create(message_record)  # type: ignore[attr-defined]

                logger.info(
                    "[SUCCESS] Mensagem outbound persistida: %s",
                    message_record.id,
                    extra=self._log_context(),
                )

            # Se for mensagem recebida, enfileirar para IA processar
            if self.message_direction == "inbound":
                needs_ai_processing = True
                logger.debug(
                    "Mensagem enfileirada para processamento de IA",
                    extra=self._log_context(),
                )
            else:
                needs_ai_processing = False

            return {
                "status": "success",
                "message_id": message_record.id,
                "conversation_id": self.conversation_id,
                "needs_ai_processing": needs_ai_processing,
                "phone": self.message_data.get("phone"),
            }

        except ValueError as e:
            logger.error(
                "Erro de validação: %s",
                e,
                extra=self._log_context(),
            )
            raise
        except Exception as e:  # noqa: BLE001 (blind exception)
            logger.error(
                "Erro ao processar mensagem: %s: %s",
                type(e).__name__,
                e,
                extra=self._log_context(),
            )

            # Se for erro de conexão com BD, retry
            if "database" in str(e).lower() or "connection" in str(e).lower():
                raise JobRetryableError(f"Erro de BD: {e}") from e
            raise JobRetryableError(f"Erro inesperado: {e}") from e
class MessageBatchProcessingJob(BaseJob):
    """
    Job para processar lote de mensagens (útil para sincronização).

    Responsabilidades:
    - Processar múltiplas mensagens em sequência
    - Aplicar throttling para anti-ban
    - Reportar progresso
    """

    def __init__(
        self,
        messages: list[dict[str, Any]],
        conversation_id: str | None = None,
        **kwargs,
    ):
        """
        Inicializar job de lote.

        Args:
            messages: Lista de payloads de mensagem
            conversation_id: ID da conversa
            **kwargs: Argumentos herdados
        """
        super().__init__(**kwargs)

        self.messages = messages
        self.conversation_id = conversation_id

    def execute(self) -> dict[str, Any]:
        """
        Executar processamento em lote.

        Returns:
            Dict com resultado: processadas, falhadas, total
        """
        processed = 0
        failed = 0

        logger.info(
            "Iniciando processamento em lote de %s mensagens",
            len(self.messages),
            extra=self._log_context(),
        )

        for idx, msg_data in enumerate(self.messages):
            try:
                job = MessageProcessingJob(
                    message_data=msg_data,
                    conversation_id=self.conversation_id,
                    attempt=0,
                )
                job.run()
                processed += 1

            except (ValueError, JobRetryableError) as e:
                logger.warning(
                    "Falha ao processar mensagem %s: %s",
                    idx + 1,
                    e,
                    extra=self._log_context(),
                )
                failed += 1

        logger.info(
            "Lote processado: %s OK, %s falhadas",
            processed,
            failed,
            extra=self._log_context(),
        )

        return {
            "status": "completed",
            "processed": processed,
            "failed": failed,
            "total": len(self.messages),
        }
