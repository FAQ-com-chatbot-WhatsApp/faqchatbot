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

    def get_all(self, phone_number: str | None = None) -> list[LeadModel]:
        """
        Get all leads, optionally filtered by phone_number.

        Args:
            phone_number: Phone number to filter by

        Returns:
            List of LeadModel
        """
        query = self.session.query(LeadModel)
        if phone_number:
            query = query.filter(LeadModel.phone_number == phone_number)
        return query.all()
