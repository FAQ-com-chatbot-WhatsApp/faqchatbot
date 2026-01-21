"""Repository for conversation persistence and retrieval operations."""

from datetime import UTC, datetime

from sqlalchemy import select
from sqlalchemy.orm import Session, joinedload

from robbot.adapters.repositories.base_repository import BaseRepository
from robbot.domain.enums import ConversationStatus
from robbot.infra.db.models.conversation_model import ConversationModel
from robbot.infra.db.models.lead_model import LeadModel


class ConversationRepository(BaseRepository[ConversationModel]):
    """Data access layer for conversations."""

    def __init__(self, db: Session):
        """Initialize repository with database session.

        Args:
            db: SQLAlchemy session
        """
        super().__init__(db, ConversationModel)

    def get_by_chat_id(self, chat_id: str) -> ConversationModel | None:
        """Get conversation by WhatsApp chat ID.

        Args:
            chat_id: WhatsApp chat ID (e.g., '5511999999999@c.us')

        Returns:
            Conversation or None if not found
        """
        stmt = (
            select(ConversationModel)
            .options(joinedload(ConversationModel.lead))
            .where(ConversationModel.chat_id == chat_id)
        )
        return self.db.scalars(stmt).first()

    def get_by_id(self, id: str) -> ConversationModel | None:
        """Get conversation by ID with lead loaded.

        Args:
            id: Conversation ID

        Returns:
            Conversation or None if not found
        """
        stmt = select(ConversationModel).options(joinedload(ConversationModel.lead)).where(ConversationModel.id == id)
        return self.db.scalars(stmt).first()

    def update_status(
        self,
        conversation_id: str,
        status: ConversationStatus,
    ) -> ConversationModel:
        """Update conversation status.

        Args:
            conversation_id: Conversation UUID
            status: New status

        Returns:
            Updated conversation
        """
        conversation = self.get_by_id(conversation_id)
        if not conversation:
            raise ValueError(f"Conversation {conversation_id} not found")
        conversation.status = status
        conversation.updated_at = datetime.now(UTC)
        self.db.flush()
        self.db.refresh(conversation)
        return conversation

    def update_last_message_at(
        self,
        conversation_id: str,
    ) -> ConversationModel:
        """Update last message timestamp.

        Args:
            conversation_id: Conversation UUID

        Returns:
            Updated conversation
        """
        conversation = self.get_by_id(conversation_id)
        if not conversation:
            raise ValueError(f"Conversation {conversation_id} not found")
        conversation.last_message_at = datetime.now(UTC)
        self.db.flush()
        self.db.refresh(conversation)
        return conversation

    def get_active(self, limit: int = 100) -> list[ConversationModel]:
        """Get active conversations.

        Args:
            limit: Max number of conversations to return

        Returns:
            List of active conversations
        """
        stmt = (
            select(ConversationModel)
            .where(ConversationModel.status == ConversationStatus.ACTIVE)
            .order_by(ConversationModel.last_message_at.desc())
            .limit(limit)
        )
        return list(self.db.scalars(stmt).all())

    def get_by_status(
        self,
        status: ConversationStatus,
        limit: int = 100,
    ) -> list[ConversationModel]:
        """Get conversations by status.

        Args:
            status: Conversation status to filter by
            limit: Max number of conversations to return

        Returns:
            List of conversations with given status
        """
        stmt = (
            select(ConversationModel)
            .where(ConversationModel.status == status)
            .order_by(ConversationModel.updated_at.desc())
            .limit(limit)
        )
        return list(self.db.scalars(stmt).all())

    def find_by_criteria(
        self,
        filters: dict,
        limit: int = 50,
        offset: int = 0,
    ) -> list[ConversationModel]:
        """Find conversations by dynamic criteria.

        Args:
            filters: Dict with optional keys:
                - status: ConversationStatus
                - assigned_to_user_id: int (from Lead)
                - created_after: datetime
                - created_before: datetime
            limit: Max number of conversations to return
            offset: Number of conversations to skip

        Returns:
            List of conversations matching criteria
        """
        stmt = select(ConversationModel).options(joinedload(ConversationModel.lead))

        # Apply filters dynamically
        if "status" in filters and filters["status"]:
            stmt = stmt.where(ConversationModel.status == filters["status"])

        if "phone_number" in filters and filters["phone_number"]:
            stmt = stmt.where(ConversationModel.phone_number == filters["phone_number"])

        if "assigned_to_user_id" in filters and filters["assigned_to_user_id"]:
            # Join with Lead to filter by assigned_to_user_id
            stmt = stmt.join(LeadModel, ConversationModel.id == LeadModel.conversation_id)
            stmt = stmt.where(LeadModel.assigned_to_user_id == filters["assigned_to_user_id"])

        if "created_after" in filters and filters["created_after"]:
            stmt = stmt.where(ConversationModel.created_at >= filters["created_after"])

        if "created_before" in filters and filters["created_before"]:
            stmt = stmt.where(ConversationModel.created_at <= filters["created_before"])

        # Order and paginate
        stmt = stmt.order_by(ConversationModel.updated_at.desc()).limit(limit).offset(offset)

        return list(self.db.scalars(stmt).all())
