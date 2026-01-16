"""Repository for Lead entity."""

from sqlalchemy.orm import Session

from robbot.adapters.repositories.base_repository import BaseRepository
from robbot.infra.db.models.lead_model import LeadModel


class LeadRepository(BaseRepository[LeadModel]):
    """Repository for leads CRUD operations."""

    def __init__(self, session: Session):
        """Initialize repository with database session."""
        super().__init__(session, LeadModel)

    def get_by_phone(self, phone_number: str) -> LeadModel | None:
        """
        Get lead by phone number.

        Args:
            phone_number: Phone number

        Returns:
            LeadModel or None
        """
        return self.session.query(LeadModel).filter_by(phone_number=phone_number).first()
