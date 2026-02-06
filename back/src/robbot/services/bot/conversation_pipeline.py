"""
Conversation Pipeline - Orchestrates initial message processing and context building.
"""

import logging
from typing import Any
from sqlalchemy.orm import Session

from robbot.services.communication.message_processor import MessageProcessor
from robbot.services.ai.context_builder import ContextBuilder
from robbot.services.ai.intent_detector import IntentDetector
from robbot.services.ai.context_validator import ContextValidator
from robbot.infra.persistence.models.conversation_model import ConversationModel

logger = logging.getLogger(__name__)


class PipelineState:
    """Carries state through the pipeline."""
    def __init__(self, message_text: str):
        self.message_text = message_text
        self.context_text = ""
        self.intent = "OUTRO"
        self.spin_phase = "S"
        self.is_urgent = False
        self.new_score = 0
        self.validation_reason = None


class ConversationPipeline:
    """
    Handles the ingestion and analysis of inbound messages.
    """
    def __init__(
        self, 
        session: Session,
        transcription_service: Any,
        vector_store: Any,
        llm: Any,
        prompt_templates: Any
    ):
        self.session = session
        self.message_processor = MessageProcessor(session, transcription_service)
        self.context_builder = ContextBuilder(vector_store)
        self.intent_detector = IntentDetector(llm, prompt_templates)
        self.validator = ContextValidator(min_similarity_score=0.65)

    async def execute(
        self, 
        conversation: ConversationModel,
        message_inner_text: str,
        has_audio: bool = False,
        audio_url: str | None = None,
        has_video: bool = False,
        video_url: str | None = None,
    ) -> PipelineState:
        """
        Execute the ingestion pipeline.
        """
        state = PipelineState(message_inner_text)
        
        # 1. Process media
        state.message_text = await self.message_processor.process_media_message(
            state.message_text, has_audio, audio_url, has_video, video_url
        )

        # 2. Save inbound message
        await self.message_processor.save_inbound_message(
            self.session, conversation.id, state.message_text, from_phone=conversation.phone_number
        )

        # 3. Fetch context
        raw_context = await self.context_builder.get_conversation_context(conversation.id)

        # 4. Validate context
        validation = await self.validator.validate_context(
            user_message=state.message_text, 
            retrieved_context=raw_context, 
            conversation_id=conversation.id
        )
        
        if validation["is_valid"]:
            state.context_text = validation["filtered_context"]
        else:
            state.validation_reason = validation.get("reason", "Unknown")
            state.context_text = ""

        # 5. Detect intent and urgency
        state.intent, state.spin_phase = await self.intent_detector.detect_intent(state.message_text, state.context_text)
        state.is_urgent = await self.intent_detector.detect_urgency(state.message_text, state.context_text)

        # 6. Try extract name
        if conversation.lead:
            lead_name = conversation.lead.name
            should_extract = (
                not lead_name 
                or lead_name == conversation.lead.phone_number
                or (len(lead_name.split()) == 1 and len(lead_name) < 15)
            )
            if should_extract:
                await self.intent_detector.try_extract_name(
                    self.session, state.message_text, state.context_text, conversation
                )

        # 7. Update Score (Pre-calculation)
        state.new_score = await self.intent_detector.update_maturity_score(
            self.session, conversation, state.message_text, state.intent, state.spin_phase
        )

        return state

