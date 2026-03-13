# pylint: disable=no-member,invalid-name,line-too-long
"""Add is_read field to conversation_messages

Revision ID: d1e2f3a4b5c6
Revises: 439174cb8c5e
Create Date: 2026-03-13 00:00:00.000000

"""

from collections.abc import Sequence

from alembic import op

# revision identifiers, used by Alembic.
revision: str = "d1e2f3a4b5c6"
down_revision: str | Sequence[str] | None = "439174cb8c5e"
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None


def upgrade() -> None:
    """Add is_read column to conversation_messages table."""
    # Add is_read column (default False for existing messages)
    op.execute("ALTER TABLE conversation_messages ADD COLUMN IF NOT EXISTS is_read BOOLEAN DEFAULT false NOT NULL")
    op.execute("COMMENT ON COLUMN conversation_messages.is_read IS 'Whether message has been read'")

    # Add index for filtering by is_read
    op.execute("CREATE INDEX IF NOT EXISTS ix_conversation_messages_is_read ON conversation_messages (is_read)")


def downgrade() -> None:
    """Remove is_read column from conversation_messages table."""
    op.execute("DROP INDEX IF EXISTS ix_conversation_messages_is_read")
    op.execute("ALTER TABLE conversation_messages DROP COLUMN IF EXISTS is_read")
