# pylint: disable=no-member,invalid-name,line-too-long
"""Make lead name nullable

Revision ID: e4b5d6f7a8b9
Revises: d1e2f3a4b5c6
Create Date: 2026-03-31 16:20:00.000000

"""

from collections.abc import Sequence

import sqlalchemy as sa
from alembic import op

# revision identifiers, used by Alembic.
revision: str = "e4b5d6f7a8b9"
down_revision: str | Sequence[str] | None = "d1e2f3a4b5c6"
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None


def upgrade() -> None:
    """Upgrade schema: make leads.name nullable."""
    op.alter_column(
        "leads",
        "name",
        existing_type=sa.String(length=255),
        nullable=True,
        existing_comment="Lead name",
    )


def downgrade() -> None:
    """Downgrade schema: make leads.name not nullable."""
    op.alter_column(
        "leads",
        "name",
        existing_type=sa.String(length=255),
        nullable=False,
        existing_comment="Lead name",
    )
