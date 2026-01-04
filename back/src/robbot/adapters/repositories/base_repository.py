"""Base repository with common CRUD operations."""

from typing import Generic, TypeVar

from sqlalchemy import func, select
from sqlalchemy.orm import Session

ModelType = TypeVar("ModelType")
class BaseRepository(Generic[ModelType]):
    """Generic repository with common database operations."""

    def __init__(self, db: Session, model_class: type[ModelType]):
        """
        Initialize repository.

        Args:
            db: Database session
            model_class: SQLAlchemy ORM Model class
        """
        self.session = db
        self.db = db
        self.model_class = model_class

    def get_by_id(self, entity_id: int) -> ModelType | None:
        """Get entity by ID."""
        return self.db.get(self.model_class, entity_id)

    def get_all(self, skip: int = 0, limit: int = 100) -> list[ModelType]:
        """Get all entities with pagination."""
        stmt = select(self.model_class).offset(skip).limit(limit)
        return list(self.db.scalars(stmt).all())

    def create(self, obj: ModelType) -> ModelType:
        """Create new entity."""
        self.db.add(obj)
        self.db.flush()
        self.db.refresh(obj)
        return obj

    def update(self, obj: ModelType) -> ModelType:
        """Update existing entity."""
        self.db.flush()
        self.db.refresh(obj)
        return obj

    def delete(self, obj: ModelType) -> None:
        """Delete entity."""
        self.db.delete(obj)
        self.db.flush()

    def count(self) -> int:
        """Count total entities."""
        stmt = select(func.count()).select_from(self.model_class)
        return self.db.scalar(stmt) or 0

