"""HandoffService - Manages seamless bot→human transition."""

import logging
from datetime import UTC, datetime

from sqlalchemy.orm import Session

from robbot.core.custom_exceptions import BusinessRuleError, NotFoundException
from robbot.domain.shared.enums import ConversationStatus, LeadStatus
from robbot.infra.persistence.models.conversation_model import ConversationModel
from robbot.infra.persistence.repositories.conversation_repository import ConversationRepository
from robbot.infra.persistence.repositories.lead_repository import LeadRepository

logger = logging.getLogger(__name__)


class HandoffService:
    """
    Service to manage bot→human handoff.

    Responsibilities:
    - Trigger handoff when high score or bot confused
    - Assign conversation to attendant
    - Mark as completed after scheduling
    - Generate natural transition messages
    """

    def __init__(
        self,
        conversation_repo: ConversationRepository,
        lead_repo: LeadRepository,
    ):
        self.conversation_repo = conversation_repo
        self.lead_repo = lead_repo

    async def trigger_handoff(
        self,
        session: Session,
        conversation_id: str,
        reason: str,
        score: int | None = None,
        additional_context: str | None = None,
    ) -> dict:
        """
        Trigger bot→human handoff.

        Args:
            conversation_id: Conversation ID
            reason: Reason (score_high, bot_confused, manual)
            score: Current maturity score
            additional_context: Additional context

        Returns:
            dict with status and transition message
        """
        conversation = self.conversation_repo.get_by_id(conversation_id)
        if not conversation:
            raise NotFoundException(f"Conversation {conversation_id} not found")

        # Validate allowed states
        if conversation.status == ConversationStatus.PENDING_HANDOFF:
            logger.info("[SKIP] Handoff already triggered for conversation: %s", conversation_id)
            return {
                "status": "already_pending",
                "conversation_id": conversation_id,
                "message": "Só um minutinho que já tô verificando a agenda aqui pra você, tá bom? 😊",
            }
        if conversation.status in [
            ConversationStatus.COMPLETED,
            ConversationStatus.CLOSED,
        ]:
            raise BusinessRuleError(f"Cannot handoff conversation in status {conversation.status}")

        # Update status, reason, and escalation timestamp
        conversation.status = ConversationStatus.PENDING_HANDOFF
        conversation.escalation_reason = reason
        conversation.escalated_at = datetime.now(UTC)
        conversation.updated_at = datetime.now(UTC)

        self.conversation_repo.update(conversation)
        session.flush()

        # Create notifications for the team (SOLID: delegate to NotificationService)
        from robbot.infra.persistence.repositories.user_repository import UserRepository
        from robbot.services.communication.notification_service import NotificationService

        notif_service = NotificationService(session)
        user_repo = UserRepository(session)

        # Determine target users: lead's assigned user OR all active team members
        target_user_ids = []
        if conversation.lead and conversation.lead.assigned_to_user_id:
            target_user_ids = [conversation.lead.assigned_to_user_id]
        else:
            # If no one is assigned, notify everyone active
            active_users = user_repo.list_all()
            target_user_ids = [u.id for u in active_users if u.is_active]

        lead_name = (
            conversation.lead.name if conversation.lead and conversation.lead.name else (conversation.phone_number or "Cliente")
        )

        # Consistent urgency flag based on reason
        is_urgent = reason in ["urgencia_detectada", "URGENCIA_DOR"]

        for uid in target_user_ids:
            notif_service.notify_handoff(
                user_id=uid,
                conversation_id=conversation.id,
                lead_name=lead_name,
                is_urgent=is_urgent,
            )

        session.flush()

        # Invalidate analytics cache
        from robbot.infra.redis.client import invalidate_analytics_cache
        invalidate_analytics_cache()

        logger.info(
            "[SUCCESS] Handoff triggered and notifications created: conv=%s, reason=%s", conversation_id, reason
        )

        # Generate natural transition message based on context
        transition_message = self._generate_transition_message(reason, score)

        return {
            "status": "handoff_triggered",
            "conversation_id": conversation_id,
            "reason": reason,
            "message": transition_message,
        }

    async def assign_to_human(
        self,
        session: Session,
        conversation_id: str,
        user_id: str,
    ) -> ConversationModel:
        """
        Assign conversation to human attendant.

        Args:
            conversation_id: Conversation ID
            user_id: Attendant UUID

        Returns:
            Updated conversation
        """
        conversation = self.conversation_repo.get_by_id(conversation_id)
        if not conversation:
            raise NotFoundException(f"Conversation {conversation_id} not found")

        # Validate state
        if conversation.status not in [
            ConversationStatus.PENDING_HANDOFF,
            ConversationStatus.ACTIVE_BOT,
            ConversationStatus.ESCALATED,
        ]:
            raise BusinessRuleError(f"Cannot assign conversation in status {conversation.status}")

        # Assign to attendant
        conversation.status = ConversationStatus.ACTIVE_HUMAN
        conversation.assigned_to = user_id
        conversation.assigned_at = datetime.now(UTC)
        conversation.updated_at = datetime.now(UTC)

        self.conversation_repo.update(conversation)
        session.flush()

        # Invalidate analytics cache
        from robbot.infra.redis.client import invalidate_analytics_cache
        invalidate_analytics_cache()

        logger.info("[SUCCESS] Conversation assigned: conv=%s, user=%s", conversation_id, user_id)

        return conversation

    async def mark_as_completed(
        self,
        session: Session,
        conversation_id: str,
        user_id: str,
    ) -> dict:
        """
        Mark conversation as completed after scheduling.

        Args:
            conversation_id: Conversation ID
            user_id: UUID of attendant who confirmed

        Returns:
            dict with calculated metrics
        """
        conversation = self.conversation_repo.get_by_id(conversation_id)
        if not conversation:
            raise NotFoundException(f"Conversation {conversation_id} not found")

        # Validate state
        if conversation.status != ConversationStatus.ACTIVE_HUMAN:
            raise BusinessRuleError(
                f"Can only complete conversations in ACTIVE_HUMAN status, got {conversation.status}"
            )

        # Validate assignment
        if conversation.assigned_to != user_id:
            raise BusinessRuleError(
                f"User {user_id} cannot complete conversation assigned to {conversation.assigned_to}"
            )

        # Mark as completed
        conversation.status = ConversationStatus.COMPLETED
        conversation.completed_at = datetime.now(UTC)
        conversation.updated_at = datetime.now(UTC)

        # Update lead to converted
        if conversation.lead:
            lead = self.lead_repo.get_by_id(conversation.lead.id)
            if lead:
                lead.status = LeadStatus.SCHEDULED
                lead.maturity_score = 100
                lead.converted_at = datetime.now(UTC)
                lead.updated_at = datetime.now(UTC)
                self.lead_repo.update(lead)

        self.conversation_repo.update(conversation)
        session.flush()

        # Invalidate analytics cache
        try:
            from robbot.infra.redis.client import invalidate_analytics_cache
            invalidate_analytics_cache()
        except Exception:
            pass

        # Calculate metrics
        metrics = self._calculate_metrics(conversation)

        logger.info("[SUCCESS] Conversation completed: conv=%s, metrics=%s", conversation_id, metrics)

        return {
            "status": "completed",
            "conversation_id": conversation_id,
            "metrics": metrics,
        }

    def _generate_transition_message(self, reason: str, score: int | None = None) -> str:
        """Generates natural transition message based on context."""
        messages = {
            "score_high": (
                "Vejo que você está bem interessada em cuidar desse ponto! 🎯\n\n"
                "Deixa eu só dar uma olhadinha aqui na agenda pra ver os horários disponíveis "
                "pra gente já deixar tudo certinho pra você. Só um instantinho que já te dou um retorno, tá?"
            ),
            "bot_confused": (
                "Entendo que essa é uma dúvida bem específica! "
                "Deixa eu só dar uma verificada rápida aqui pra poder te dar "
                "a resposta exata, viu? Um minutinho só."
            ),
            "manual": (
                "Ó, já tô vendo aqui os detalhes pra você. Só um instantinho que "
                "já verifico a agenda pra gente resolver isso da melhor forma! 😊"
            ),
            "urgencia_detectada": (
                "Entendi! Fica tranquila que eu já tô dando uma olhada aqui pra ver como consigo "
                "te encaixar o mais rápido possível, viu? Só um segundinho!"
            ),
        }

        return messages.get(
            reason,
            "Só um minutinho que eu já tô verificando a agenda aqui pra você, tá? 😊",
        )

    def _calculate_metrics(self, conversation: ConversationModel) -> dict:
        """Calculates conversion metrics."""
        metrics = {}

        # Total conversation time
        if conversation.created_at and conversation.completed_at:
            total_time = conversation.completed_at - conversation.created_at
            metrics["total_conversation_time_minutes"] = int(total_time.total_seconds() / 60)

        # Time to handoff
        if conversation.created_at and conversation.assigned_at:
            handoff_time = conversation.assigned_at - conversation.created_at
            metrics["time_to_handoff_minutes"] = int(handoff_time.total_seconds() / 60)

        # Human interaction time
        if conversation.assigned_at and conversation.completed_at:
            human_time = conversation.completed_at - conversation.assigned_at
            metrics["human_interaction_time_minutes"] = int(human_time.total_seconds() / 60)

        # Final score
        if conversation.lead and conversation.lead.maturity_score:
            metrics["final_score"] = conversation.lead.maturity_score

        # Escalation reason
        if conversation.escalation_reason:
            metrics["escalation_reason"] = conversation.escalation_reason

        return metrics

    async def return_to_bot(
        self,
        session: Session,
        conversation_id: str,
        user_id: str,
    ) -> ConversationModel:
        """
        Returns conversation to bot (if human decides).

        Args:
            conversation_id: Conversation ID
            user_id: UUID of attendant returning the chat

        Returns:
            Updated conversation
        """
        conversation = self.conversation_repo.get_by_id(conversation_id)
        if not conversation:
            raise NotFoundException(f"Conversation {conversation_id} not found")

        # Validate state and assignment
        if conversation.status != ConversationStatus.ACTIVE_HUMAN:
            raise BusinessRuleError("Can only return conversations in ACTIVE_HUMAN status")

        if conversation.assigned_to != user_id:
            raise BusinessRuleError(f"User {user_id} cannot return conversation assigned to {conversation.assigned_to}")

        # Return to bot
        conversation.status = ConversationStatus.ACTIVE_BOT
        conversation.assigned_to = None
        conversation.assigned_at = None
        conversation.escalation_reason = None
        conversation.updated_at = datetime.now(UTC)

        self.conversation_repo.update(conversation)
        session.flush()

        # Invalidate analytics cache
        try:
            from robbot.infra.redis.client import invalidate_analytics_cache
            invalidate_analytics_cache()
        except Exception:
            pass

        logger.info("[SUCCESS] Conversation returned to bot: conv=%s, by_user=%s", conversation_id, user_id)

        return conversation
