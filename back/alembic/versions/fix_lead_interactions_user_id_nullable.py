"""Fix lead_interactions user_id to allow NULL for bot interactions

Revision ID: fix_lead_interactions_user_id
Revises: a1b2c3d4e5f6
Create Date: 2026-02-01 10:00:00.000000

"""
from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = 'fix_lead_interactions_user_id'
down_revision = 'a1b2c3d4e5f6'
branch_labels = None
depends_on = None


def upgrade():
    """Make user_id nullable to support bot interactions."""
    # Alter user_id to allow NULL values
    op.alter_column('lead_interactions', 'user_id',
               existing_type=sa.Integer(),
               nullable=True)


def downgrade():
    """Revert user_id to NOT NULL."""
    # First delete rows where user_id is NULL
    op.execute('DELETE FROM lead_interactions WHERE user_id IS NULL')
    
    # Then make column NOT NULL again
    op.alter_column('lead_interactions', 'user_id',
               existing_type=sa.Integer(),
               nullable=False)
