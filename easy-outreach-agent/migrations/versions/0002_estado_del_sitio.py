"""estado del sitio y fecha de enriquecimiento en companies

Revision ID: 0002
Revises: 0001
Create Date: 2026-10-15 17:00:00
"""
from collections.abc import Sequence

import sqlalchemy as sa
from alembic import op

revision: str = '0002'
down_revision: str | None = '0001'
branch_labels: str | Sequence[str] | None = None
depends_on: str | Sequence[str] | None = None

SITE_STATUSES = "'ok', 'unreachable', 'http_error', 'parked', 'robots', 'not_html', 'no_site'"


def upgrade() -> None:
    with op.batch_alter_table('companies', schema=None) as batch_op:
        batch_op.add_column(sa.Column('site_status', sa.String(length=20), nullable=True))
        batch_op.add_column(sa.Column('enriched_at', sa.DateTime(timezone=True), nullable=True))
        batch_op.create_check_constraint(
            'site_status', f"site_status IS NULL OR site_status IN ({SITE_STATUSES})"
        )


def downgrade() -> None:
    with op.batch_alter_table('companies', schema=None) as batch_op:
        batch_op.drop_constraint(op.f('ck_companies_site_status'), type_='check')
        batch_op.drop_column('enriched_at')
        batch_op.drop_column('site_status')
