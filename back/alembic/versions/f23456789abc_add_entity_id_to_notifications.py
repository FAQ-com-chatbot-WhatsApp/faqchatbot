"""add entity_id to notifications

Revision ID: f23456789abc
Revises: e4b5d6f7a8b9
Create Date: 2026-03-31 21:18:00.000000

"""
from collections.abc import Sequence
from alembic import op
import sqlalchemy as sa

# revision identifiers, used by Alembic.
revision: str = 'f23456789abc'
down_revision: str | Sequence[str] | None = 'e4b5d6f7a8b9'
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None


def upgrade() -> None:
    # Add entity_id column to notifications table
    op.add_column('notifications', sa.Column('entity_id', sa.String(length=36), nullable=True))
    # Add index for faster lookups
    op.create_index(op.f('ix_notifications_entity_id'), 'notifications', ['entity_id'], unique=False)


def downgrade() -> None:
    # Remove index and column
    op.drop_index(op.f('ix_notifications_entity_id'), table_name='notifications')
    op.drop_column('notifications', 'entity_id')
