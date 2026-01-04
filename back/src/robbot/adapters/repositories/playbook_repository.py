"""Repository for playbook persistence and retrieval operations."""


from sqlalchemy.orm import Session, joinedload

from robbot.adapters.repositories.base_repository import BaseRepository
from robbot.infra.db.models.playbook_model import PlaybookModel


class PlaybookRepository(BaseRepository[PlaybookModel]):
    """Repository encapsulating DB access for playbooks."""

    def __init__(self, db: Session):
        super().__init__(db, PlaybookModel)

    def get_by_topic_id(self, topic_id: str, active_only: bool = False, include_steps: bool = False, limit: int = 100, offset: int = 0) -> list[PlaybookModel]:
        """List playbooks by topic with pagination.
        
        Args:
            topic_id: Topic ID to filter by
            active_only: If True, return only active playbooks (default: False)
            include_steps: If True, eager-load playbook steps (default: False)
            limit: Maximum number of records to return (default: 100)
            offset: Number of records to skip (default: 0)
        
        Returns:
            List of playbook model instances
        """
        query = self.db.query(PlaybookModel).filter(PlaybookModel.topic_id == topic_id)
        if active_only:
            query = query.filter(PlaybookModel.active == True)
        if include_steps:
            query = query.options(joinedload(PlaybookModel.steps))
        return query.limit(limit).offset(offset).all()

    def get_by_name(self, search_term: str, active_only: bool = False, limit: int = 100, offset: int = 0) -> list[PlaybookModel]:
        """Search playbooks by name (case-insensitive partial match) with pagination.
        
        Args:
            search_term: Search term to match against playbook names
            active_only: If True, return only active playbooks (default: False)
            limit: Maximum number of records to return (default: 100)
            offset: Number of records to skip (default: 0)
        
        Returns:
            List of playbook model instances matching the search term
        """
        query = self.db.query(PlaybookModel).filter(
            PlaybookModel.name.ilike(f"%{search_term}%")
        )
        if active_only:
            query = query.filter(PlaybookModel.active == True)
        return query.limit(limit).offset(offset).all()

    def get_active(self, skip: int = 0, limit: int = 100) -> list[PlaybookModel]:
        """List active playbooks with pagination."""
        query = self.db.query(PlaybookModel).filter(PlaybookModel.active == True)
        return query.offset(skip).limit(limit).all()
