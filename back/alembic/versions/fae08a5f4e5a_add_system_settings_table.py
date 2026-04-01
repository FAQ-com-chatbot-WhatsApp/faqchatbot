"""Add system settings table

Revision ID: fae08a5f4e5a
Revises: f23456789abc
Create Date: 2026-04-01 01:15:00.000000

"""

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = 'fae08a5f4e5a'
down_revision = 'f23456789abc'
branch_labels = None
depends_on = None


def upgrade():
    """Create system_settings table."""
    op.create_table(
        'system_settings',
        sa.Column('key', sa.String(length=100), nullable=False),
        sa.Column('value', sa.Text(), nullable=True),
        sa.Column('description', sa.String(length=255), nullable=True),
        sa.PrimaryKeyConstraint('key')
    )
    op.create_index(op.f('ix_system_settings_key'), 'system_settings', ['key'], unique=False)


def downgrade():
    """Drop system_settings table."""
    op.drop_index(op.f('ix_system_settings_key'), table_name='system_settings')
    op.drop_table('system_settings')
