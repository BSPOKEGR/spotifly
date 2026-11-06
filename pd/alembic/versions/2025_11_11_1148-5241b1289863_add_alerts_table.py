"""add_alerts_table

Revision ID: 5241b1289863
Revises: 9949dfb9da1e
Create Date: 2025-11-11 11:48:22.639206

"""
from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql

# revision identifiers, used by Alembic.
revision: str = '5241b1289863'
down_revision: Union[str, None] = '9949dfb9da1e'
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    op.create_table(
        'alerts',
        sa.Column('id', postgresql.UUID(as_uuid=True), primary_key=True, server_default=sa.text('gen_random_uuid()')),
        sa.Column('parcel_id', postgresql.UUID(as_uuid=True), nullable=False, index=True),
        sa.Column('date', sa.DateTime(), nullable=False, index=True),
        sa.Column('model_name', sa.String(length=50), nullable=False),
        sa.Column('sub_type', sa.String(length=100), nullable=False),
        sa.Column('risk_level', sa.Integer(), nullable=False),
        sa.Column('degree_days', sa.Float(), nullable=True),
        sa.Column('extra_data', sa.Text(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=False, server_default=sa.text('NOW()')),
    )
    
    # Create indexes
    op.create_index('idx_alerts_parcel_id', 'alerts', ['parcel_id'])
    op.create_index('idx_alerts_date', 'alerts', ['date'])
    op.create_index('idx_alerts_model_name', 'alerts', ['model_name'])


def downgrade() -> None:
    op.drop_index('idx_alerts_model_name', table_name='alerts')
    op.drop_index('idx_alerts_date', table_name='alerts')
    op.drop_index('idx_alerts_parcel_id', table_name='alerts')
    op.drop_table('alerts')
