"""
Conversation Orchestrator - orquestra fluxo completo de conversação.

REFATORADO (Sprint 9 - I2):
Este módulo foi simplificado, delegando responsabilidades para:
- MessageProcessor: processamento de áudio/vídeo/texto
- ContextBuilder: gerenciamento de contexto via ChromaDB
- IntentDetector: detecção de intenção, urgência e score
- ConversationOrchestrator: apenas coordenação high-level

O orchestrador agora coordena:
1. Recebimento de mensagens
2. Detecção de silenciamento (status da conversa)
3. Coordenação dos processadores especializados
4. Handoff para humanos
5. Logging e persistência
"""

import logging
from datetime import UTC, datetime
from typing import Any

from robbot.adapters.external.gemini_client import get_gemini_client
from robbot.adapters.external.waha_client import WAHAClient
from robbot.adapters.repositories.conversation_repository import ConversationRepository
from robbot.adapters.repositories.lead_interaction_repository import LeadInteractionRepository
from robbot.adapters.repositories.lead_repository import LeadRepository
from robbot.adapters.repositories.llm_interaction_repository import LLMInteractionRepository
from robbot.config.prompts import get_prompt_templates
from robbot.core.custom_exceptions import (
    BusinessRuleError,
    DatabaseError,
    LLMError,
    WAHAError,
)
from robbot.domain.enums import (
    ConversationStatus,
    InteractionType,
)
from robbot.infra.db.models.conversation_model import ConversationModel
from robbot.infra.db.models.lead_interaction_model import LeadInteractionModel
from robbot.infra.db.models.lead_model import LeadModel
from robbot.infra.db.models.llm_interaction_model import LLMInteractionModel
from robbot.infra.db.session import get_sync_session
from robbot.services.context_builder import ContextBuilder
from robbot.services.handoff_service import HandoffService
from robbot.services.intent_detector import IntentDetector
from robbot.services.message_processor import MessageProcessor
from robbot.services.playbook_tools import PLAYBOOK_TOOLS_DECLARATIONS

logger = logging.getLogger(__name__)


class ConversationOrchestrator:
    """
    Orquestrador central do fluxo de conversação (SIMPLIFICADO).

    Responsabilidades:
    - Coordenar componentes especializados (MessageProcessor, ContextBuilder, IntentDetector)
    - Gerenciar estado da conversa e regras de negócio
    - Orquestrar handoffs e escalações
    - Logging e persistência de interações
    """

    def __init__(self):
        self.gemini_client = get_gemini_client(tools=PLAYBOOK_TOOLS_DECLARATIONS)
        self.prompt_templates = get_prompt_templates()
        self.waha_client = WAHAClient()

        # Componentes especializados
        self.message_processor = MessageProcessor()
        self.context_builder = ContextBuilder()
        self.intent_detector = IntentDetector(self.gemini_client, self.prompt_templates)

        logger.info("[SUCCESS] ConversationOrchestrator initialized with specialized components")

    async def process_inbound_message(
        self,
        chat_id: str,
        phone_number: str,
        message_text: str,
        session_name: str = "default",
        has_audio: bool = False,
        audio_url: str | None = None,
        has_video: bool = False,
        video_url: str | None = None,
    ) -> dict[str, Any]:
        """
        Processar mensagem inbound e gerar resposta.

        FLUXO SIMPLIFICADO:
        1. Buscar ou criar conversa
        2. Processar mídia (MessageProcessor)
        3. Verificar se bot deve silenciar
        4. Buscar contexto (ContextBuilder)
        5. Detectar intenção e urgência (IntentDetector)
        6. Gerar resposta com Gemini
        7. Atualizar score (IntentDetector)
        8. Verificar escalação (IntentDetector)
        9. Salvar contexto (ContextBuilder)
        10. Enviar resposta e persistir

        Args:
            chat_id: ID do chat
            phone_number: Número do telefone
            message_text: Texto da mensagem
            session_name: Nome da sessão WAHA
            has_audio: Se mensagem tem áudio
            audio_url: URL do arquivo de áudio
            has_video: Se mensagem tem vídeo
            video_url: URL do arquivo de vídeo

        Returns:
            Dict com resultado

        Raises:
            BusinessRuleError: Se falhar na lógica de negócio
        """
        try:
            logger.info(
                "Processing inbound message: chat_id=%s, phone=%s, length=%s, "
                "has_audio=%s, has_video=%s",
                chat_id,
                phone_number,
                len(message_text),
                has_audio,
                has_video
            )

            with get_sync_session() as session:
                conversation = await self._get_or_create_conversation(
                    session, chat_id, phone_number
                )

                # BOT SILENCIA se humano está conversando
                if self._should_bot_silence(conversation):
                    return await self._handle_silenced_conversation(
                        session, conversation, message_text
                    )

                # Processar mídia (áudio/vídeo)
                message_text = await self.message_processor.process_media_message(
                    message_text, has_audio, audio_url, has_video, video_url
                )

                # Salvar mensagem inbound
                await self.message_processor.save_inbound_message(
                    session, conversation.id, message_text
                )

                # Buscar contexto conversacional
                context_text = await self.context_builder.get_conversation_context(
                    conversation.id
                )

                # Detectar intenção e urgência
                intent = await self.intent_detector.detect_intent(message_text, context_text)
                is_urgent = await self.intent_detector.detect_urgency(message_text, context_text)

                # Atualizar urgência se detectada
                if is_urgent and not conversation.is_urgent:
                    await self._mark_as_urgent(session, conversation)

                # Extrair nome se ainda não temos
                if conversation.lead and conversation.lead.name == conversation.lead.phone_number:
                    await self.intent_detector.try_extract_name(
                        session, message_text, context_text, conversation
                    )

                # Gerar resposta
                response_data = await self._generate_response(
                    message_text=message_text,
                    intent=intent,
                    context=context_text,
                    conversation=conversation,
                )

                response_text = response_data["response"]

                # Solicitar nome se apropriado
                response_text = await self._append_name_request_if_needed(
                    conversation, context_text, response_text
                )

                # Atualizar score
                new_score = await self.intent_detector.update_maturity_score(
                    session, conversation, message_text, intent
                )

                # Verificar escalação
                should_escalate = await self.intent_detector.check_escalation_needed(
                    conversation, intent, message_text, new_score
                )

                if should_escalate:
                    response_text = await self._handle_handoff(
                        session, conversation, new_score
                    )

                # Salvar contexto no ChromaDB
                await self.context_builder.save_to_chroma(
                    conversation.id,
                    f"User: {message_text}\nBot: {response_text}",
                    {"intent": intent, "score": new_score}
                )

                # Enviar resposta via WAHA
                sent = await self._send_response_via_waha(
                    chat_id, response_text, session_name
                )

                # Salvar mensagem outbound
                await self.message_processor.save_outbound_message(
                    session, conversation.id, response_text
                )

                # Registrar interação
                await self._register_interaction(
                    session,
                    conversation.lead_id,
                    intent,
                    f"Inbound: {message_text[:50]}... | Outbound: {response_text[:50]}..."
                )

                # Log LLM interaction
                await self._log_llm_interaction(
                    session,
                    conversation.id,
                    f"Intent: {intent} | {message_text[:100]}",
                    response_text[:200],
                    response_data.get("tokens_used", 0),
                    response_data.get("latency_ms", 0)
                )

                session.commit()

                logger.info(
                    "[SUCCESS] Message processed successfully (conv_id=%s, "
                    "intent=%s, score=%s, sent=%s)",
                    conversation.id,
                    intent,
                    new_score,
                    sent
                )

                return {
                    "conversation_id": conversation.id,
                    "response_sent": sent,
                    "response_text": response_text,
                    "intent": intent,
                    "maturity_score": new_score,
                }

        except Exception as e:  # noqa: BLE001
            logger.error(
                "[ERROR] Failed to process message: %s",
                e,
                exc_info=True,
                extra={"chat_id": chat_id, "phone": phone_number}
            )

            # Tentar fallback
            try:
                fallback_response = await self._generate_fallback_response(str(e))
                await self._send_response_via_waha(chat_id, fallback_response, session_name)
            except (LLMError, WAHAError) as fallback_error:
                logger.error("[ERROR] Fallback failed: %s", fallback_error)

            raise BusinessRuleError(f"Failed to process message: {e}") from e

    # =========================================================================
    # MÉTODOS PRIVADOS - Lógica de Coordenação
    # =========================================================================

    def _should_bot_silence(self, conversation: ConversationModel) -> bool:
        """Verificar se bot deve silenciar (humano conversando)"""
        return conversation.status in [
            ConversationStatus.ACTIVE_HUMAN,
            ConversationStatus.PENDING_HANDOFF,
            ConversationStatus.COMPLETED,
            ConversationStatus.CLOSED,
        ]

    async def _handle_silenced_conversation(
        self,
        session: Any,
        conversation: ConversationModel,
        message_text: str
    ) -> dict[str, Any]:
        """Processar mensagem quando bot está silenciado"""
        await self.message_processor.save_inbound_message(
            session, conversation.id, message_text
        )

        logger.info(
            "🤐 Bot silenciado: conversa em status %s (conv_id=%s)",
            conversation.status,
            conversation.id
        )

        # TODO: Notificar atendente via WebSocket
        # await self.notification_service.notify_user(
        #     conversation.assigned_to,
        #     f"Nova mensagem de {conversation.phone_number}"
        # )

        session.commit()

        return {
            "conversation_id": conversation.id,
            "response_sent": False,
            "bot_silenced": True,
            "status": conversation.status.value,
        }

    async def _mark_as_urgent(
        self,
        session: Any,
        conversation: ConversationModel
    ) -> None:
        """Marcar conversa como urgente"""
        conversation.is_urgent = True
        conv_repo = ConversationRepository(session)
        conv_repo.update(conversation)
        session.flush()

        logger.info("🚨 Urgência detectada (conv_id=%s)", conversation.id)

    async def _append_name_request_if_needed(
        self,
        conversation: ConversationModel,
        context: str,
        response_text: str
    ) -> str:
        """Adicionar solicitação de nome se apropriado"""
        should_ask_name = (
            conversation.lead
            and conversation.lead.name == conversation.lead.phone_number
            and 20 <= conversation.lead.maturity_score < 50
        )

        if should_ask_name:
            name_request = await self.intent_detector.generate_name_request(
                context,
                conversation.lead.maturity_score
            )

            if name_request:
                response_text = f"{response_text}\n\n{name_request}"

        return response_text

    async def _handle_handoff(
        self,
        session: Any,
        conversation: ConversationModel,
        score: int
    ) -> str:
        """Executar handoff para humano e retornar mensagem de transição"""
        handoff_service = HandoffService(
            ConversationRepository(session),
            LeadRepository(session)
        )

        escalation_reason = "score_high" if score >= 85 else "bot_confused"

        handoff_result = await handoff_service.trigger_handoff(
            session=session,
            conversation_id=conversation.id,
            reason=escalation_reason,
            score=score,
        )

        logger.info(
            "Automatic handoff triggered: conv=%s, reason=%s, score=%s",
            conversation.id,
            escalation_reason,
            score
        )

        return handoff_result["message"]

    async def _get_or_create_conversation(
        self,
        session: Any,
        chat_id: str,
        phone_number: str
    ) -> ConversationModel:
        """
        Buscar conversa existente ou criar nova com lead associado.

        Returns:
            ConversationModel: Conversa existente ou recém-criada

        Raises:
            DatabaseError: Se falhar ao criar
        """
        repo = ConversationRepository(session)
        conversation = repo.get_by_chat_id(chat_id)

        if conversation:
            logger.info("[SUCCESS] Conversation found (id=%s)", conversation.id)
            return conversation

        # Criar novo lead
        lead_repo = LeadRepository(session)
        lead = LeadModel(
            phone_number=phone_number,
            name=phone_number,
            maturity_score=0,
        )
        lead_repo.create(lead)
        session.flush()

        # Criar nova conversa
        conversation = ConversationModel(  # type: ignore[call-arg]
            chat_id=chat_id,
            phone_number=phone_number,
            status=ConversationStatus.ACTIVE,
            lead_id=lead.id
        )
        repo.create(conversation)
        session.flush()

        logger.info(
            "[SUCCESS] New conversation created (id=%s, lead_id=%s)",
            conversation.id,
            lead.id
        )

        return conversation

    async def _generate_response(
        self,
        message_text: str,
        intent: str,
        context: str,
        conversation: ConversationModel
    ) -> dict[str, Any]:
        """
        Gerar resposta contextualizada usando Gemini.

        Returns:
            dict: {"response": str, "tokens_used": int, "latency_ms": int}

        Raises:
            LLMError: Se Gemini falhar após retries
        """
        prompt = self.prompt_templates.format_response_prompt(
            user_message=message_text,
            intent=intent,
            context=context,
            maturity_score=conversation.lead.maturity_score if conversation.lead else 0,
            lead_status=conversation.lead.status.value if conversation.lead else 'NEW',
            last_interaction="Agora"
        )

        response_data = self.gemini_client.generate_response(prompt)

        logger.info("[SUCCESS] Response generated (%s tokens)", response_data['tokens_used'])

        return response_data

    async def _send_response_via_waha(
        self,
        chat_id: str,
        text: str,
        session: str
    ) -> bool:
        """
        Enviar mensagem via WAHA.

        Returns:
            bool: True se enviado com sucesso

        Raises:
            WAHAError: Se falhar ao enviar
        """
        try:
            await self.waha_client.send_text(
                chatId=chat_id,
                text=text
            )

            logger.info("[SUCCESS] Response sent via WAHA (chat_id=%s)", chat_id)

            return True

        except WAHAError:
            raise
        except Exception as e:  # noqa: BLE001
            logger.error("[ERROR] Failed to send via WAHA: %s", e)
            raise WAHAError(f"Failed to send message: {e}", original_error=e)

    async def _register_interaction(
        self,
        session: Any,
        lead_id: str | None,
        interaction_type: str,
        notes: str
    ) -> None:
        """
        Registrar interação no histórico do lead.

        Raises:
            DatabaseError: Se falhar ao registrar
        """
        if not lead_id:
            return

        try:
            repo = LeadInteractionRepository(session)

            type_map = {
                "INTERESSE_PRODUTO": InteractionType.MESSAGE,
                "ORCAMENTO": InteractionType.MEETING,
                "AGENDAMENTO": InteractionType.MEETING,
                "RECLAMACAO": InteractionType.CALL,
            }

            interaction = LeadInteractionModel(
                lead_id=lead_id,
                interaction_type=type_map.get(interaction_type, InteractionType.MESSAGE),
                notes=notes,
                timestamp=datetime.now(UTC),
            )

            repo.create(interaction)
            session.flush()

            logger.info("[SUCCESS] Interaction registered (lead_id=%s)", lead_id)

        except DatabaseError:
            raise
        except Exception as e:  # noqa: BLE001
            logger.warning("[WARNING] Failed to register interaction: %s", e)
            raise DatabaseError(f"Failed to register interaction: {e}")

    async def _log_llm_interaction(
        self,
        session: Any,
        conversation_id: str,
        prompt: str,
        response: str,
        tokens: int,
        latency_ms: int
    ) -> None:
        """
        Registrar interação com LLM para auditoria.

        Raises:
            DatabaseError: Se falhar ao registrar
        """
        try:
            repo = LLMInteractionRepository(session)

            interaction = LLMInteractionModel(
                conversation_id=conversation_id,
                prompt_text=prompt,
                response_text=response,
                tokens_used=tokens,
                latency_ms=latency_ms,
                timestamp=datetime.now(UTC),
            )

            repo.create(interaction)
            session.flush()

            logger.info("[SUCCESS] LLM interaction logged (conv_id=%s)", conversation_id)

        except DatabaseError:
            raise
        except Exception as e:  # noqa: BLE001
            logger.warning("[WARNING] Failed to log LLM interaction: %s", e)
            raise DatabaseError(f"Failed to log LLM interaction: {e}")

    async def _generate_fallback_response(self, error: str) -> str:
        """
        Gerar resposta de fallback quando ocorre erro.

        Returns:
            str: Mensagem de fallback amigável
        """
        try:
            prompt = self.prompt_templates.format_fallback_prompt(
                situation="Erro ao processar mensagem",
                error=error
            )

            response = self.gemini_client.generate_response(prompt, max_retries=1)

            return response["response"]

        except LLMError:
            return (
                "Desculpe, estou com dificuldades técnicas no momento. "
                "Um atendente humano entrará em contato em breve."
            )


# Singleton global
_orchestrator: ConversationOrchestrator | None = None


def get_conversation_orchestrator() -> ConversationOrchestrator:
    """
    Obter instância singleton do orchestrador.

    Returns:
        ConversationOrchestrator singleton
    """
    global _orchestrator

    if _orchestrator is None:
        _orchestrator = ConversationOrchestrator()
        logger.info("🎯 ConversationOrchestrator inicializado como singleton")

    return _orchestrator
