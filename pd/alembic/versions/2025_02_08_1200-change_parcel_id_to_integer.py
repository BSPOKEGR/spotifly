"""change_parcel_id_to_integer

Revision ID: a1b2c3d4e5f6
Revises: 5241b1289863
Create Date: 2025-02-08 12:00:00.000000

"""
from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql

# revision identifiers, used by Alembic.
revision: str = 'a1b2c3d4e5f6'
down_revision: Union[str, None] = '5241b1289863'
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    # Drop existing indexes
    op.drop_index('idx_alerts_parcel_id', table_name='alerts')
    
    # Change parcel_id column from UUID to Integer
    # If there's existing data, this will fail - table should be empty or data migrated first
    op.alter_column('alerts', 'parcel_id',
                    existing_type=postgresql.UUID(as_uuid=True),
                    type_=sa.Integer(),
                    existing_nullable=False,
                    postgresql_using='NULL')  # Will set existing values to NULL (requires manual data migration if data exists)
    
    # Add foreign key constraint to parcel table
    op.create_foreign_key(
        'fk_alerts_parcel_id',
        'alerts', 'parcel',
        ['parcel_id'], ['id'],
        ondelete='CASCADE'
    )
    
    # Recreate index
    op.create_index('idx_alerts_parcel_id', 'alerts', ['parcel_id'])


def downgrade() -> None:
    # Drop foreign key
    op.drop_constraint('fk_alerts_parcel_id', 'alerts', type_='foreignkey')
    
    # Drop index
    op.drop_index('idx_alerts_parcel_id', table_name='alerts')
    
    # Change parcel_id back to UUID
    op.alter_column('alerts', 'parcel_id',
                    existing_type=sa.Integer(),
                    type_=postgresql.UUID(as_uuid=True),
                    existing_nullable=False,
                    postgresql_using='gen_random_uuid()')
    
    # Recreate index
    op.create_index('idx_alerts_parcel_id', 'alerts', ['parcel_id'])
