"""Repository for ConversationMessage entity."""

import logging

from sqlalchemy.orm import Session

from robbot.infra.persistence.models.conversation_message_model import ConversationMessageModel
from robbot.infra.persistence.repositories.base_repository import BaseRepository

logger = logging.getLogger(__name__)


class ConversationMessageRepository(BaseRepository[ConversationMessageModel]):
    """Repository for conversation messages CRUD operations."""

    def __init__(self, session: Session):
        """Initialize repository with database session."""
        super().__init__(session, ConversationMessageModel)

    def get_by_conversation(self, conversation_id: str, limit: int = 50) -> list[ConversationMessageModel]:
        """
        Get messages by conversation ID.

        Args:
            conversation_id: Conversation ID
            limit: Maximum number of messages

        Returns:
            List of messages ordered by timestamp
        """
        return (
            self.session.query(ConversationMessageModel)
            .filter_by(conversation_id=conversation_id)
            .order_by(ConversationMessageModel.created_at.desc())
            .limit(limit)
            .all()[::-1]  # Invert to return in chronological order
        )

    def mark_conversation_as_read(self, conversation_id: str) -> int:
        """
        Mark all INBOUND messages in a conversation as read.

        Args:
            conversation_id: Conversation ID

        Returns:
            Number of messages marked as read
        """
        updated_count = (
            self.session.query(ConversationMessageModel)
            .filter_by(conversation_id=conversation_id, is_read=False)
            .filter(ConversationMessageModel.direction == "INBOUND")
            .update({"is_read": True}, synchronize_session=False)
        )
        self.session.commit()
        logger.info(f"Marked {updated_count} messages as read in conversation {conversation_id}")
        return updated_count
