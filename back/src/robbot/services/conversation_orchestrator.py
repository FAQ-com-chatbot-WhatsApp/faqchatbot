"""
Conversation Orchestrator - Orchestrates the complete conversation flow.

This module delegates responsibilities to:
- MessageProcessor: audio/video/text processing
- ContextBuilder: context management via ChromaDB
- IntentDetector: intent detection, urgency, and scoring
- ConversationOrchestrator: high-level coordination only

The orchestrator coordinates:
1. Inbound message reception
2. Silence detection (conversation status)
3. Coordination of specialized processors
4. Handoff to humans
5. Logging and persistence
"""

import ast
import logging
from typing import Any

from robbot.adapters.external.chroma_vector_store import ChromaVectorStore
from robbot.adapters.external.gemini_client import get_gemini_client
from robbot.adapters.external.waha_client import WAHAClient
from robbot.adapters.repositories.conversation_repository import (
    ConversationRepository,
)
from robbot.adapters.repositories.lead_interaction_repository import (
    LeadInteractionRepository,
)
from robbot.adapters.repositories.lead_repository import LeadRepository
from robbot.adapters.repositories.llm_interaction_repository import (
    LLMInteractionRepository,
)
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
from robbot.infra.db.models.llm_interaction_model import (
    LLMInteractionModel,
)
from robbot.infra.db.session import get_sync_session
from robbot.services.answered_questions import AnsweredQuestionsMemory
from robbot.services.context_builder import ContextBuilder
from robbot.services.context_validator import (
    ContextValidator,
    ResponseDeduplicator,
)
from robbot.services.handoff_service import HandoffService
from robbot.services.intent_detector import IntentDetector
from robbot.services.message_processor import MessageProcessor
from robbot.services.text_sanitizer import enforce_whatsapp_style
from robbot.services.transcription_service import TranscriptionService


logger = logging.getLogger(__name__)


class ConversationOrchestrator:
    """
    Central orchestrator for the conversation flow.

    Responsibilities:
    - Coordinate specialized components (MessageProcessor, ContextBuilder, IntentDetector)
    - Manage conversation state and business rules
    - Orchestrate handoffs and escalations
    - Log and persist interactions
    """

    def __init__(self):
        self.gemini_client = get_gemini_client()
        self.prompt_templates = get_prompt_templates()
        self.waha_client = WAHAClient()
        # Specialized services without DB dependency
        self.transcription_service = TranscriptionService()
        self.vector_store = ChromaVectorStore()
        self.answered_questions_memory = AnsweredQuestionsMemory()  # Memory for answered questions

        logger.info("[SUCCESS] ConversationOrchestrator initialized")

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
        Process inbound message and generate response.

        FLOW:
        1. Get or create conversation
        2. Process media (MessageProcessor)
        3. Check if bot should silence
        4. Build context (ContextBuilder)
        5. Detect intent and urgency (IntentDetector)
        6. Generate response with Gemini
        7. Update score (IntentDetector)
        8. Check escalation (IntentDetector)
        9. Save context (ContextBuilder)
        10. Send response and persist

        Args:
            chat_id: Chat ID
            phone_number: Phone number
            message_text: Message text
            session_name: WAHA session name
            has_audio: If message has audio
            audio_url: Audio file URL
            has_video: If message has video
            video_url: Video file URL

        Returns:
            Dict with result

        Raises:
            BusinessRuleError: If business logic fails
        """
        try:
            logger.info(
                "Processing inbound message: chat_id=%s, phone=%s, length=%s, has_audio=%s, has_video=%s",
                chat_id,
                phone_number,
                len(message_text),
                has_audio,
                has_video,
            )

            with get_sync_session() as session:
                # Initialize components with DB session
                message_processor = MessageProcessor(session, self.transcription_service)
                context_builder = ContextBuilder(self.vector_store)
                intent_detector = IntentDetector(self.gemini_client, self.prompt_templates)

                conversation = await self._get_or_create_conversation(session, chat_id, phone_number)

                # Bot silences if human is chatting
                if self._should_bot_silence(conversation):
                    return await self._handle_silenced_conversation(session, conversation, message_text)

                # Process media (audio/video)
                message_text = await message_processor.process_media_message(
                    message_text, has_audio, audio_url, has_video, video_url
                )

                # Save inbound message
                await message_processor.save_inbound_message(
                    session, conversation.id, message_text, from_phone=conversation.phone_number
                )

                # Persist conversation/lead/message early to avoid losing data on downstream failures
                session.commit()

                # Get conversational context
                raw_context = await context_builder.get_conversation_context(conversation.id)

                # SAFEGUARD: Validate context before using it
                validator = ContextValidator(min_similarity_score=0.65)
                validation = await validator.validate_context(
                    user_message=message_text, retrieved_context=raw_context, conversation_id=conversation.id
                )

                if not validation["is_valid"]:
                    logger.warning(
                        "[CONTEXT_VALIDATION] Context validation failed for conversation %s: %s",
                        conversation.id,
                        validation.get("reason", "Unknown"),
                    )
                    context_text = ""  # Use empty context instead of contaminated
                else:
                    context_text = validation["filtered_context"]

                # Detect intent and urgency
                intent, spin_phase = await intent_detector.detect_intent(message_text, context_text)
                is_urgent = await intent_detector.detect_urgency(message_text, context_text)

                # Update urgency if detected
                if is_urgent and not conversation.is_urgent:
                    await self._mark_as_urgent(session, conversation)

                # Extract name if we don't have it yet (None, empty, or phone number placeholder)
                if conversation.lead:
                    lead_name = conversation.lead.name
                    # Try extraction if: no name, empty name, phone placeholder, or single word (incomplete)
                    should_extract = (
                        not lead_name  # None or empty
                        or lead_name == conversation.lead.phone_number  # Phone placeholder
                        or (len(lead_name.split()) == 1 and len(lead_name) < 15)  # Single word < 15 chars (incomplete)
                    )

                    logger.debug(
                        "[DEBUG] Name extraction check: lead_name='%s', should_extract=%s",
                        lead_name,
                        should_extract,
                    )

                    if should_extract:
                        # Store previous name for comparison
                        previous_name = conversation.lead.name
                        await intent_detector.try_extract_name(session, message_text, context_text, conversation)

                        # LID RESOLUTION: If name was extracted/updated, try to resolve LID
                        logger.info(
                            "[LID_DEBUG] After name extraction: lead.name='%s', previous='%s', phone='%s', is_lid=%s",
                            conversation.lead.name,
                            previous_name,
                            conversation.lead.phone_number,
                            "@lid" in conversation.lead.phone_number,
                        )
                        if conversation.lead.name and conversation.lead.name != previous_name:
                            from robbot.services.lid_resolver_service import get_lid_resolver

                            lid_resolver = get_lid_resolver()
                            logger.info(
                                "[LID] Name change detected ('%s' -> '%s'), attempting progressive resolution for lead %s (phone=%s)",
                                previous_name,
                                conversation.lead.name,
                                conversation.lead.id,
                                conversation.lead.phone_number,
                            )
                            try:
                                resolved = await lid_resolver.resolve_and_update_lead(
                                    lead_id=conversation.lead.id,
                                    current_phone=conversation.lead.phone_number,
                                    lead_name=conversation.lead.name,
                                    session=session,
                                )
                                if resolved:
                                    logger.info(
                                        "[LID] Lead phone updated after name extraction: lead_id=%s",
                                        conversation.lead.id,
                                    )
                            except Exception as e:
                                logger.warning("[LID] Error resolving LID after name extraction: %s", str(e))
                else:
                    logger.warning("[WARNING] No lead attached to conversation, skipping name extraction")

                # Checagem de pergunta já respondida
                if self.answered_questions_memory.was_answered(message_text):
                    response_text = "Já respondi essa pergunta antes! Se precisar de mais detalhes, me avise. 😊"
                    sent = await self._send_response_via_waha(chat_id, response_text, session_name)
                    await message_processor.save_outbound_message(
                        session, conversation.id, response_text, to_phone=conversation.phone_number
                    )
                    return {
                        "conversation_id": conversation.id,
                        "response_sent": sent,
                        "response_text": response_text,
                        "intent": "REPETIDA",
                        "maturity_score": 0,
                    }

                # Generate response
                response_data = await self._generate_response(
                    message_text=message_text,
                    intent=intent,
                    spin_phase=spin_phase,
                    context=context_text,
                    conversation=conversation,
                )

                response_text = self._normalize_response_text(response_data["response"])

                # DEBUG: Log response type and value
                logger.debug(
                    "[DEBUG] response_text type=%s, value=%s", type(response_text).__name__, str(response_text)[:200]
                )

                # Update score (baseado em fase SPIN)
                new_score = await intent_detector.update_maturity_score(
                    session, conversation, message_text, intent, spin_phase
                )

                # Request name if appropriate (use updated score)
                response_text = await self._append_name_request_if_needed(
                    conversation, context_text, response_text, spin_phase, new_score
                )

                response_text = self._normalize_response_text(response_text)

                # Check escalation
                should_escalate = await intent_detector.check_escalation_needed(
                    conversation, intent, message_text, new_score
                )

                if should_escalate:
                    response_text = await self._handle_handoff(session, conversation, new_score)

                # SAFEGUARD: Check for duplicate responses before sending
                deduplicator = ResponseDeduplicator(max_age_seconds=300)
                if deduplicator.is_duplicate(conversation.id, response_text):
                    logger.warning(
                        "[DEDUPLICATION] Duplicate response detected for conversation %s, skipping send",
                        conversation.id,
                    )
                    # Store that we skipped this to avoid retrying
                    deduplicator.record_response(conversation.id, response_text)
                    # Skip sending and return early
                    session.commit()
                    return {
                        "conversation_id": conversation.id,
                        "response_sent": False,
                        "duplicate_response": True,
                        "status": conversation.status.value,
                    }

                # Record this response as sent
                deduplicator.record_response(conversation.id, response_text)

                # Save context to ChromaDB (ONLY user message, not bot response)
                # Storing bot responses creates feedback loop and contaminates the vector store
                await context_builder.save_to_chroma(
                    conversation.id,
                    f"User: {message_text}",  # Only save user message
                    {"intent": intent, "score": new_score},
                )

                # DEBUG: Log before send
                logger.debug(
                    "[DEBUG] Before WAHA send - response_text type=%s, value=%s",
                    type(response_text).__name__,
                    str(response_text)[:200],
                )

                # Send response via WAHA
                sent = await self._send_response_via_waha(chat_id, response_text, session_name)

                # Save outbound message
                await message_processor.save_outbound_message(
                    session, conversation.id, response_text, to_phone=conversation.phone_number
                )

                # Register interaction
                await self._register_interaction(
                    session,
                    conversation.lead.id if conversation.lead else None,
                    intent,
                    f"Inbound: {message_text[:50]}... | Outbound: {response_text[:50]}...",
                )

                # Log LLM interaction
                await self._log_llm_interaction(
                    session,
                    conversation.id,
                    f"Intent: {intent} | {message_text[:100]}",
                    response_text[:200],
                    response_data.get("tokens_used", 0),
                    response_data.get("latency_ms", 0),
                )

                session.commit()

                logger.info(
                    "[SUCCESS] Message processed successfully (conv_id=%s, intent=%s, score=%s, sent=%s)",
                    conversation.id,
                    intent,
                    new_score,
                    sent,
                )

                # Após gerar resposta, registrar pergunta como respondida
                self.answered_questions_memory.add(message_text)

                return {
                    "conversation_id": conversation.id,
                    "response_sent": sent,
                    "response_text": response_text,
                    "intent": intent,
                    "maturity_score": new_score,
                }

        except Exception as e:
            logger.error(
                "[ERROR] Failed to process message: %s",
                e,
                exc_info=True,
                extra={"chat_id": chat_id, "phone": phone_number},
            )

            # Rollback any partial changes
            session.rollback()

            # Try fallback only for non-database errors

            try:
                fallback_response = await self._generate_fallback_response(str(e))
                # Garante padrão WhatsApp mesmo em fallback
                fallback_response = enforce_whatsapp_style(fallback_response)
                await self._send_response_via_waha(chat_id, fallback_response, session_name)

                # Save fallback message to database
                message_processor = MessageProcessor(session, self.transcription_service)
                conversation = await self._get_or_create_conversation(session, chat_id, phone_number)

                await message_processor.save_outbound_message(
                    session, conversation.id, fallback_response, to_phone=phone_number
                )

                session.commit()
                logger.info("[SUCCESS] Fallback response sent and saved (conv_id=%s)", conversation.id)

            except (LLMError, WAHAError) as fallback_error:
                logger.error("[ERROR] Fallback failed: %s", fallback_error)
                session.rollback()

            raise BusinessRuleError(f"Failed to process message: {e}") from e

    # =========================================================================
    # PRIVATE METHODS - Coordination Logic
    # =========================================================================

    def _should_bot_silence(self, conversation: ConversationModel) -> bool:
        """Check if bot should silence (human is chatting)."""
        return conversation.status in [
            ConversationStatus.ACTIVE_HUMAN,
            ConversationStatus.PENDING_HANDOFF,
            ConversationStatus.COMPLETED,
            ConversationStatus.CLOSED,
        ]

    async def _handle_silenced_conversation(
        self, session: Any, conversation: ConversationModel, message_text: str
    ) -> dict[str, Any]:
        """Process message when bot is silenced."""
        # Instanciar MessageProcessor localmente (como em process_inbound_message)
        message_processor = MessageProcessor(session, self.transcription_service)
        await message_processor.save_inbound_message(
            session, conversation.id, message_text, from_phone=conversation.phone_number
        )

        logger.info("🤐 Bot silenced: conversation in status %s (conv_id=%s)", conversation.status, conversation.id)

        session.commit()

        return {
            "conversation_id": conversation.id,
            "response_sent": False,
            "bot_silenced": True,
            "status": conversation.status.value,
        }

    async def _mark_as_urgent(self, session: Any, conversation: ConversationModel) -> None:
        """Mark conversation as urgent."""
        conversation.is_urgent = True
        conv_repo = ConversationRepository(session)
        conv_repo.update(conversation)
        session.flush()

        logger.info("[INFO] Urgency detected (conv_id=%s)", conversation.id)

    async def _append_name_request_if_needed(
        self,
        conversation: ConversationModel,
        context: str,
        response_text: str,
        spin_phase: str,
        maturity_score: int,
    ) -> str:
        """Append name request if appropriate."""
        lead_name = conversation.lead.name if conversation.lead else None
        lead_phone = conversation.lead.phone_number if conversation.lead else None
        should_ask_name = conversation.lead and (not lead_name or lead_name == lead_phone) and 20 <= maturity_score < 50

        if should_ask_name:
            # Instanciar IntentDetector localmente (como em process_inbound_message)
            intent_detector = IntentDetector(self.gemini_client, self.prompt_templates)
            name_request = await intent_detector.generate_name_request(context, spin_phase, maturity_score)

            if name_request:
                response_text = f"{response_text}\n\n{name_request}"

        return response_text

    async def _handle_handoff(self, session: Any, conversation: ConversationModel, score: int) -> str:
        """Execute handoff to human and return transition message."""
        handoff_service = HandoffService(ConversationRepository(session), LeadRepository(session))

        escalation_reason = "score_high" if score >= 85 else "bot_confused"

        handoff_result = await handoff_service.trigger_handoff(
            session=session,
            conversation_id=conversation.id,
            reason=escalation_reason,
            score=score,
        )

        logger.info(
            "Automatic handoff triggered: conv=%s, reason=%s, score=%s", conversation.id, escalation_reason, score
        )

        return handoff_result["message"]

    async def _get_or_create_conversation(self, session: Any, chat_id: str, phone_number: str) -> ConversationModel:
        """
        Get existing conversation or create new one with associated lead.

        Returns:
            ConversationModel: Existing or newly created conversation

        Raises:
            DatabaseError: If creation fails
        """
        repo = ConversationRepository(session)
        conversation = repo.get_by_chat_id(chat_id)

        if conversation:
            logger.info("[SUCCESS] Conversation found (id=%s)", conversation.id)
            return conversation

        # Create new conversation
        conversation = ConversationModel(chat_id=chat_id, phone_number=phone_number, status=ConversationStatus.ACTIVE)
        repo.create(conversation)
        session.flush()

        # LID RESOLUTION: Try quick resolution before creating lead
        resolved_phone = phone_number
        from robbot.services.lid_resolver_service import get_lid_resolver

        lid_resolver = get_lid_resolver()
        if lid_resolver.is_lid_format(phone_number):
            try:
                resolved = await lid_resolver.try_resolve_lid(phone_number, timeout_seconds=1.0)
                if resolved:
                    resolved_phone = resolved
                    logger.info("[LID] Phone resolved at conversation creation: %s -> %s", phone_number, resolved_phone)
            except Exception as e:
                logger.debug("[LID] Could not resolve at creation, will retry later: %s", str(e))

        # Create new lead associated with conversation
        lead_repo = LeadRepository(session)
        lead = LeadModel(
            phone_number=resolved_phone,
            name=resolved_phone,  # Use resolved phone as placeholder
            maturity_score=0,
            conversation_id=conversation.id,
        )
        lead_repo.create(lead)
        conversation.lead = lead
        session.flush()

        logger.info("[SUCCESS] New conversation and lead created (conv_id=%s, lead_id=%s)", conversation.id, lead.id)

        return conversation

    async def _generate_response(
        self, message_text: str, intent: str, spin_phase: str, context: str, conversation: ConversationModel
    ) -> dict[str, Any]:
        """
        Generate contextual response using Gemini.

        Returns:
            dict: {"response": str, "tokens_used": int, "latency_ms": int}

        Raises:
            LLMError: If Gemini fails after retries
        """
        prompt = self.prompt_templates.format_response_prompt(
            user_message=message_text,
            intent=intent,
            spin_phase=spin_phase,
            context=context,
            lead_name=conversation.lead.name if conversation.lead else None,
            maturity_score=conversation.lead.maturity_score if conversation.lead else 0,
            lead_status=conversation.lead.status.value if conversation.lead else "NEW",
            last_interaction="Agora",
        )

        # DEBUG: Log do prompt completo (primeiros 800 caracteres)
        logger.info("[PROMPT_DEBUG] Generated prompt (first 800 chars): %s...", prompt[:800])

        response_data = self.gemini_client.generate_response(prompt)

        logger.info("[SUCCESS] Response generated (%s tokens)", response_data["tokens_used"])

        return response_data

    async def _send_response_via_waha(self, chat_id: str, text: str, session: str) -> bool:
        """
        Send message via WAHA.

        Returns:
            bool: True if sent successfully

        Raises:
            WAHAError: If sending fails
        """
        try:
            await self.waha_client.send_text(chat_id=chat_id, text=text, session=session)

            logger.info("[SUCCESS] Response sent via WAHA (chat_id=%s)", chat_id)

            return True

        except WAHAError as e:
            # Check if this is a session status error (common in tests)
            if "Session status is not as expected" in str(e) or "STOPPED" in str(e):
                logger.warning("[SKIP] WAHA session not ready, skipping response send (chat_id=%s): %s", chat_id, e)
                return False  # Don't raise, just skip sending
            raise
        except Exception as e:
            logger.error("[ERROR] Failed to send via WAHA: %s", e)
            raise WAHAError(f"Failed to send message: {e}", original_error=e) from e

    def _normalize_response_text(self, response_text: Any) -> str:
        """Normaliza e aplica enforce_whatsapp_style para garantir resposta curta e limpa."""
        if isinstance(response_text, dict):
            text = str(response_text.get("text", response_text))
        elif isinstance(response_text, list):
            text = " ".join(str(item) for item in response_text)
        elif isinstance(response_text, str) and response_text.lstrip().startswith("{") and "'text'" in response_text:
            try:
                parsed = ast.literal_eval(response_text)
                if isinstance(parsed, dict) and "text" in parsed:
                    text = str(parsed["text"])
                else:
                    text = str(response_text)
            except (ValueError, SyntaxError):
                text = str(response_text)
        else:
            text = str(response_text)
        # Aplica pós-processamento para WhatsApp
        return enforce_whatsapp_style(text, max_paragraphs=2)

    def _enforce_paragraph_limit(self, text: str, max_paragraphs: int = 2) -> str:
        """Enforce maximum paragraph limit for WhatsApp messages."""
        paragraphs = [p.strip() for p in text.split("\n\n") if p.strip()]

        if len(paragraphs) <= max_paragraphs:
            return text

        # Keep first max_paragraphs and truncate
        limited = "\n\n".join(paragraphs[:max_paragraphs])

        logger.warning(
            "[RESPONSE_TRUNCATE] Response had %s paragraphs, truncated to %s",
            len(paragraphs),
            max_paragraphs,
        )

        return limited

    async def _register_interaction(self, session: Any, lead_id: str | None, interaction_type: str, notes: str) -> None:
        """
        Register interaction in lead history.

        Raises:
            DatabaseError: If registration fails
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
                user_id=None,  # Bot interactions don't have a user_id (automated)
                interaction_type=type_map.get(interaction_type, InteractionType.MESSAGE),
                notes=notes,
            )

            repo.create(interaction)
            session.flush()

            logger.info("[SUCCESS] Interaction registered (lead_id=%s)", lead_id)

        except DatabaseError:
            raise
        except Exception as e:
            logger.warning("[WARNING] Failed to register interaction: %s", e)
            raise DatabaseError(f"Failed to register interaction: {e}") from e

    async def _log_llm_interaction(
        self, session: Any, conversation_id: str, prompt: str, response: str, tokens: int, latency_ms: int
    ) -> None:
        """
        Log LLM interaction for audit.

        Raises:
            DatabaseError: If registration fails
        """
        try:
            repo = LLMInteractionRepository(session)

            interaction = LLMInteractionModel(
                conversation_id=conversation_id,
                prompt=prompt,
                response=response,
                model_name="gemini-1.5-pro",  # Default model name
                tokens_used=tokens,
                latency_ms=latency_ms,
            )

            repo.create(interaction)
            session.flush()

            logger.info("[SUCCESS] LLM interaction logged (conv_id=%s)", conversation_id)

        except DatabaseError:
            raise
        except Exception as e:
            logger.warning("[WARNING] Failed to log LLM interaction: %s", e)
            raise DatabaseError(f"Failed to log LLM interaction: {e}") from e

    async def _generate_fallback_response(self, error: str) -> str:
        """
        Generate fallback response when error occurs.

        Returns:
            str: Friendly fallback message
        """
        try:
            prompt = self.prompt_templates.format_fallback_prompt(situation="Error processing message", error=error)

            response = self.gemini_client.generate_response(prompt)

            return response["response"]

        except LLMError:
            return (
                "Desculpe, estou com dificuldades técnicas no momento. Um atendente humano entrará em contato em breve."
            )


# Global Singleton
_orchestrator: ConversationOrchestrator | None = None


def get_conversation_orchestrator() -> ConversationOrchestrator:
    """
    Get singleton instance of orchestrator.

    Returns:
        ConversationOrchestrator singleton
    """
    global _orchestrator  # pylint: disable=global-statement

    if _orchestrator is None:
        _orchestrator = ConversationOrchestrator()
        logger.info("[INFO] ConversationOrchestrator initialized as singleton")

    return _orchestrator
