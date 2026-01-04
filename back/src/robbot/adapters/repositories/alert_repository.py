"""Repository for persisting alert records to the database."""

from typing import Any

from sqlalchemy.orm import Session

from robbot.adapters.repositories.base_repository import BaseRepository
from robbot.infra.db.models.alert_model import AlertModel


class AlertRepository(BaseRepository[AlertModel]):
    """
    Persistência de alerts no banco.
    """

    def __init__(self, db: Session):
        super().__init__(db, AlertModel)

    def create_alert(
        self,
        level: str,
        message: str,
        metadata: dict[str, Any] | None = None,
    ) -> AlertModel:
        """
        Cria e persiste um AlertModel.
        """
        obj = AlertModel(
            level=level,
            message=message,
            metadata_json=metadata or {},
        )
        self.db.add(obj)
        self.db.commit()
        self.db.refresh(obj)
        return obj

    def get(self, alert_id: int) -> AlertModel | None:
        """Retrieve an alert by ID."""
        return self.db.get(AlertModel, alert_id)
