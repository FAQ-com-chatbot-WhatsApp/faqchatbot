"""
Lead Service - Business logic for lead management.

This service orchestrates lead operations and status transitions.
"""

import logging
from datetime import UTC, datetime

from sqlalchemy.orm import Session

from robbot.adapters.repositories.lead_repository import LeadRepository
from robbot.core.custom_exceptions import BusinessRuleError, NotFoundException
from robbot.domain.enums import LeadStatus
from robbot.infra.db.models.lead_model import LeadModel

logger = logging.getLogger(__name__)


class LeadService:
    """
    Service to manage leads (business logic).

    Responsibilities:
    - Lead CRUD operations
    - Status transitions
    - Assignment to secretaries
    - Lead conversion and loss tracking
    """

    def __init__(self, db: Session):
        self.db = db
        self.repo = LeadRepository(db)

    def create_from_conversation(
        self,
        phone_number: str,
        name: str,
        email: str | None = None,
    ) -> LeadModel:
        """
        Create lead from conversation.

        Args:
            phone_number: Phone number
            name: Lead name
            email: Email (optional)

        Returns:
            Created lead
        """
        existing = self.repo.get_by_phone(phone_number)
        if existing:
            logger.warning("[WARNING] Lead already exists (phone=%s)", phone_number)
            return existing

        lead = LeadModel(
            phone_number=phone_number,
            name=name,
            email=email,
            maturity_score=0,
        )

        created = self.repo.create(lead)

        logger.info("[SUCCESS] Lead created (id=%s, phone=%s)", created.id, phone_number)

        return created

    def update_maturity(
        self,
        lead_id: str,
        new_score: int,
    ) -> LeadModel:
        """
        Update lead maturity score.

        Args:
            lead_id: Lead ID
            new_score: New score (0-100)

        Returns:
            Updated lead

        Raises:
            NotFoundException: If lead not found
            BusinessRuleError: If score is invalid
        """
        if not 0 <= new_score <= 100:
            raise BusinessRuleError("Maturity score must be between 0 and 100")

        lead = self.repo.get_by_id(lead_id)
        if not lead:
            raise NotFoundException(f"Lead {lead_id} not found")

        old_score = lead.maturity_score
        lead.maturity_score = new_score

        updated = self.repo.update(lead)

        logger.info("[SUCCESS] Score updated (lead_id=%s, %s -> %s)", lead_id, old_score, new_score)

        return updated

    def assign_to_user(
        self,
        lead_id: str,
        user_id: int,
    ) -> LeadModel:
        """
        Atribuir lead para secretária.

        Args:
            lead_id: ID do lead
            user_id: ID do usuário

        Returns:
            Lead atualizado

        Raises:
            NotFoundException: Se lead não existir
        """
        lead = self.repo.get_by_id(lead_id)
        if not lead:
            raise NotFoundException(f"Lead {lead_id} not found")

        lead.assigned_to_user_id = user_id
        updated = self.repo.update(lead)

        logger.info("[SUCCESS] Lead assigned (lead_id=%s, user_id=%s)", lead_id, user_id)

        return updated

    def convert(self, lead_id: str) -> LeadModel:
        """
        Marcar lead como convertido.

        Args:
            lead_id: ID do lead

        Returns:
            Lead atualizado

        Raises:
            NotFoundException: Se lead não existir
        """
        lead = self.repo.get_by_id(lead_id)
        if not lead:
            raise NotFoundException(f"Lead {lead_id} not found")

        lead.maturity_score = 100
        updated = self.repo.update(lead)

        logger.info("[SUCCESS] Lead converted (lead_id=%s)", lead_id)

        return updated

    def mark_lost(
        self,
        lead_id: str,
        reason: str | None = None,
    ) -> LeadModel:
        """
        Marcar lead como perdido.

        Args:
            lead_id: ID do lead
            reason: Motivo da perda (opcional)

        Returns:
            Lead atualizado

        Raises:
            NotFoundException: Se lead não existir
        """
        lead = self.repo.get_by_id(lead_id)
        if not lead:
            raise NotFoundException(f"Lead {lead_id} not found")

        lead.maturity_score = 0
        updated = self.repo.update(lead)

        logger.info("[SUCCESS] Lead marked as lost (lead_id=%s, reason=%s)", lead_id, reason)

        return updated

    def get_leads_by_status(
        self,
        status: LeadStatus,
        limit: int = 50,
    ) -> list[LeadModel]:
        """
        Get leads by status.

        Args:
            status: Lead status
            limit: Maximum number of results

        Returns:
            List of leads
        """
        # Fetch all leads and filter by status
        all_leads = self.repo.get_all()

        # Filtrar por status se fornecido
        if status:
            all_leads = [lead for lead in all_leads if lead.status == status]

        return all_leads[:limit]

    def get_unassigned_leads(self, limit: int = 50) -> list[LeadModel]:
        """
        Get unassigned leads.

        Args:
            limit: Maximum number of results

        Returns:
            List of leads without assignment
        """
        all_leads = self.repo.get_all()

        # Filter unassigned
        unassigned = [lead for lead in all_leads if lead.assigned_to_user_id is None]

        return unassigned[:limit]

    def list_leads(
        self,
        status: LeadStatus | None = None,
        assigned_to_user_id: int | None = None,
        min_score: int | None = None,
        unassigned_only: bool = False,
        limit: int = 50,
        offset: int = 0,
    ) -> tuple[list[LeadModel], int]:
        """
        List leads with multiple filters.

        Args:
            status: Filter by lead status
            assigned_to_user_id: Filter by assigned user
            min_score: Minimum maturity score
            unassigned_only: Show only unassigned leads
            limit: Maximum number of results
            offset: Number of results to skip

        Returns:
            Tuple of (leads list, total count)
        """
        all_leads = self.repo.get_all()

        # Apply filters
        filtered = all_leads

        if status:
            filtered = [lead for lead in filtered if lead.status == status]

        if unassigned_only:
            filtered = [lead for lead in filtered if lead.assigned_to_user_id is None]
        elif assigned_to_user_id is not None:
            filtered = [lead for lead in filtered if lead.assigned_to_user_id == assigned_to_user_id]

        if min_score is not None:
            filtered = [lead for lead in filtered if lead.maturity_score >= min_score]

        total = len(filtered)
        paginated = filtered[offset : offset + limit]

        return paginated, total

    def auto_assign_lead(self, lead_id: str) -> LeadModel | None:
        """
        Atribuir lead automaticamente para secretária disponível.

        Implementa lógica de round-robin baseada em carga de trabalho.

        Args:
            lead_id: ID do lead

        Returns:
            Lead atualizado ou None se nenhuma secretária disponível
        """
        from robbot.adapters.repositories.user_repository import UserRepository

        lead = self.repo.get_by_id(lead_id)
        if not lead:
            raise NotFoundException(f"Lead {lead_id} not found")

        # Find active secretaries
        user_repo = UserRepository(self.db)
        all_users = user_repo.get_all()

        # Filtrar secretárias (role=user e ativas)
        secretaries = [u for u in all_users if u.role == "user"]

        if not secretaries:
            logger.warning("[WARNING] No secretary available for assignment")
            return None

        # Balanceamento de carga: atribuir para secretária com menos leads ativos
        from collections import Counter

        active_leads = [
            lead
            for lead in self.repo.get_all()
            if lead.assigned_to_user_id and lead.status in [LeadStatus.ENGAGED, LeadStatus.INTERESTED]
        ]
        lead_counts = Counter(lead.assigned_to_user_id for lead in active_leads)
        selected_secretary = min(secretaries, key=lambda s: lead_counts.get(s.id, 0))

        lead.assigned_to_user_id = selected_secretary.id
        updated = self.repo.update(lead)

        logger.info("[SUCCESS] Lead auto-assigned (lead_id=%s, user_id=%s)", lead_id, selected_secretary.id)

        return updated

    def soft_delete(self, lead_id: str) -> LeadModel:
        """
        Soft delete de lead (marca deleted_at).

        Args:
            lead_id: ID do lead

        Returns:
            Lead marcado como deletado

        Raises:
            NotFoundException: Se lead não existir
        """
        lead = self.repo.get_by_id(lead_id)
        if not lead:
            raise NotFoundException(f"Lead {lead_id} not found")

        if lead.deleted_at:
            logger.warning("[WARNING] Lead was already deleted (lead_id=%s)", lead_id)
            return lead

        lead.deleted_at = datetime.now(UTC)
        updated = self.repo.update(lead)

        logger.info("[SUCCESS] Lead soft-deleted (lead_id=%s)", lead_id)

        return updated

    def restore(self, lead_id: str) -> LeadModel:
        """
        Restaurar lead soft-deleted.

        Args:
            lead_id: ID do lead

        Returns:
            Lead restaurado

        Raises:
            NotFoundException: Se lead não existir
        """
        # Fetch without filtering deleted_at
        lead = self.repo.get_by_id(lead_id)
        if not lead:
            raise NotFoundException(f"Lead {lead_id} not found")

        if not lead.deleted_at:
            logger.warning("[WARNING] Lead was not deleted (lead_id=%s)", lead_id)
            return lead

        lead.deleted_at = None
        updated = self.repo.update(lead)

        logger.info("[SUCCESS] Lead restored (lead_id=%s)", lead_id)

        return updated
