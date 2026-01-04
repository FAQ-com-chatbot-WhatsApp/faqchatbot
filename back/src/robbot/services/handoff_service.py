"""HandoffService - Manages seamless bot→human transition."""

import logging
from datetime import UTC, datetime

from sqlalchemy.orm import Session

from robbot.adapters.repositories.conversation_repository import ConversationRepository
from robbot.adapters.repositories.lead_repository import LeadRepository
from robbot.core.custom_exceptions import BusinessRuleError, NotFoundException
from robbot.domain.enums import ConversationStatus, LeadStatus
from robbot.infra.db.models.conversation_model import ConversationModel

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
        if conversation.status in [
            ConversationStatus.COMPLETED,
            ConversationStatus.CLOSED,
        ]:
            raise BusinessRuleError(
                f"Cannot handoff conversation in status {conversation.status}"
            )

        # Update status and reason
        conversation.status = ConversationStatus.PENDING_HANDOFF
        conversation.escalation_reason = reason
        conversation.updated_at = datetime.now(UTC)

        self.conversation_repo.update(conversation)
        session.flush()

        logger.info(
            f"[SUCCESS] Handoff triggered: conv={conversation_id}, reason={reason}, score={score}"
        )

        # Gerar mensagem de transição natural baseada no contexto
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
            raise BusinessRuleError(
                f"Cannot assign conversation in status {conversation.status}"
            )

        # Atribuir ao atendente
        conversation.status = ConversationStatus.ACTIVE_HUMAN
        conversation.assigned_to = user_id
        conversation.assigned_at = datetime.now(UTC)
        conversation.updated_at = datetime.now(UTC)

        self.conversation_repo.update(conversation)
        session.flush()

        logger.info(
            f"[SUCCESS] Conversation assigned: conv={conversation_id}, user={user_id}"
        )

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
        if conversation.lead_id:
            lead = self.lead_repo.get_by_id(conversation.lead_id)
            if lead:
                lead.status = LeadStatus.SCHEDULED
                lead.maturity_score = 100
                lead.updated_at = datetime.now(UTC)
                self.lead_repo.update(lead)

        self.conversation_repo.update(conversation)
        session.flush()

        # Calcular métricas
        metrics = self._calculate_metrics(conversation)

        logger.info(
            f"[SUCCESS] Conversation completed: conv={conversation_id}, metrics={metrics}"
        )

        return {
            "status": "completed",
            "conversation_id": conversation_id,
            "metrics": metrics,
        }

    def _generate_transition_message(
        self, reason: str, score: int | None = None
    ) -> str:
        """Gera mensagem de transição natural baseada no contexto."""
        messages = {
            "score_high": (
                f"Vejo que você está bem interessado (score: {score})! 🎯\n\n"
                "Vou conectar você com um de nossos especialistas para "
                "agilizar o agendamento e tirar dúvidas mais específicas. "
                "Aguarde um momento, por favor."
            ),
            "bot_confused": (
                "Entendo que você precisa de uma orientação mais específica. "
                "Vou conectar você com um atendente humano que pode te ajudar "
                "melhor nessa situação. Aguarde só um momento."
            ),
            "manual": (
                "Um de nossos atendentes vai assumir essa conversa agora "
                "para te dar um atendimento mais personalizado. 👤"
            ),
        }

        return messages.get(
            reason,
            "Vou transferir você para um atendente humano. Aguarde um momento.",
        )

    def _calculate_metrics(self, conversation: ConversationModel) -> dict:
        """Calcula métricas de conversão."""
        metrics = {}

        # Tempo total de conversa
        if conversation.created_at and conversation.completed_at:
            total_time = conversation.completed_at - conversation.created_at
            metrics["total_conversation_time_minutes"] = int(
                total_time.total_seconds() / 60
            )

        # Tempo até handoff
        if conversation.created_at and conversation.assigned_at:
            handoff_time = conversation.assigned_at - conversation.created_at
            metrics["time_to_handoff_minutes"] = int(
                handoff_time.total_seconds() / 60
            )

        # Tempo de atendimento humano
        if conversation.assigned_at and conversation.completed_at:
            human_time = conversation.completed_at - conversation.assigned_at
            metrics["human_interaction_time_minutes"] = int(
                human_time.total_seconds() / 60
            )

        # Score final
        if conversation.lead and conversation.lead.maturity_score:
            metrics["final_score"] = conversation.lead.maturity_score

        # Motivo de escalação
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
        Devolve conversa ao bot (caso humano decida).
        
        Args:
            conversation_id: ID da conversa
            user_id: UUID do atendente que está devolvendo
            
        Returns:
            Conversation atualizada
        """
        conversation = self.conversation_repo.get_by_id(conversation_id)
        if not conversation:
            raise NotFoundException(f"Conversation {conversation_id} not found")

        # Validate state and assignment
        if conversation.status != ConversationStatus.ACTIVE_HUMAN:
            raise BusinessRuleError(
                "Can only return conversations in ACTIVE_HUMAN status"
            )

        if conversation.assigned_to != user_id:
            raise BusinessRuleError(
                f"User {user_id} cannot return conversation assigned to {conversation.assigned_to}"
            )

        # Devolver ao bot
        conversation.status = ConversationStatus.ACTIVE_BOT
        conversation.assigned_to = None
        conversation.assigned_at = None
        conversation.escalation_reason = None
        conversation.updated_at = datetime.now(UTC)

        self.conversation_repo.update(conversation)
        session.flush()

        logger.info(
            f"[SUCCESS] Conversation returned to bot: conv={conversation_id}, by_user={user_id}"
        )

        return conversation
