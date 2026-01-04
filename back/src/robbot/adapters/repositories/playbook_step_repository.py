"""Repository for playbook_step persistence and retrieval operations."""
from sqlalchemy.orm import Session, joinedload

from robbot.adapters.repositories.base_repository import BaseRepository
from robbot.infra.db.models.playbook_step_model import PlaybookStepModel


class PlaybookStepRepository(BaseRepository[PlaybookStepModel]):
    """Repository encapsulating DB access for playbook steps."""

    def __init__(self, db: Session):
        super().__init__(db, PlaybookStepModel)

    def get_by_playbook_id(self, playbook_id: str, include_messages: bool = False) -> list[PlaybookStepModel]:
        """List steps by playbook in order."""
        query = self.db.query(PlaybookStepModel).filter(
            PlaybookStepModel.playbook_id == playbook_id
        ).order_by(PlaybookStepModel.step_order)

        if include_messages:
            query = query.options(joinedload(PlaybookStepModel.message))

        return query.all()

    def get_next_order(self, playbook_id: str) -> int:
        """Get next available step_order for a playbook."""
        max_order = self.db.query(PlaybookStepModel.step_order).filter(
            PlaybookStepModel.playbook_id == playbook_id
        ).order_by(PlaybookStepModel.step_order.desc()).first()

        return (max_order[0] + 1) if max_order else 1

    def reorder_steps(self, playbook_id: str, step_id_order: list[tuple[str, int]]) -> bool:
        """Reorder multiple steps at once. step_id_order = [(step_id, new_order), ...]"""
        try:
            for step_id, new_order in step_id_order:
                model = self.db.query(PlaybookStepModel).filter(
                    PlaybookStepModel.id == step_id,
                    PlaybookStepModel.playbook_id == playbook_id
                ).first()
                if model:
                    model.step_order = new_order

            self.db.commit()
            return True
        except Exception:
            self.db.rollback()
            return False
