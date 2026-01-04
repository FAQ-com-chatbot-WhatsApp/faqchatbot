"""Repository for playbook_embedding persistence and retrieval operations."""
from sqlalchemy.orm import Session

from robbot.adapters.repositories.base_repository import BaseRepository
from robbot.infra.db.models.playbook_embedding_model import PlaybookEmbeddingModel


class PlaybookEmbeddingRepository(BaseRepository[PlaybookEmbeddingModel]):
    """Repository encapsulating DB access for playbook embeddings."""

    def __init__(self, db: Session):
        super().__init__(db, PlaybookEmbeddingModel)

    def get_by_playbook_id(self, playbook_id: str) -> PlaybookEmbeddingModel | None:
        """Retrieve embedding by playbook ID."""
        return self.db.query(PlaybookEmbeddingModel).filter(
            PlaybookEmbeddingModel.playbook_id == playbook_id
        ).first()

    def get_by_chroma_id(self, chroma_doc_id: str) -> PlaybookEmbeddingModel | None:
        """Retrieve embedding by ChromaDB document ID."""
        return self.db.query(PlaybookEmbeddingModel).filter(
            PlaybookEmbeddingModel.chroma_doc_id == chroma_doc_id
        ).first()
