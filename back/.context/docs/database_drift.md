# Database Drift Report - 2026-02-10

## Issue Summary
During debugging of the WAHA polling bug, it was discovered that the database schema was out of sync with the SQLAlchemy models (`ConversationModel` and `LeadModel`). The Alembic migrations were reported as "up to date" (HEAD), but several columns were missing from the actual Postgres tables.

## Actions Taken
The following columns were manually added to the database to restore functionality without resetting the entire database:

### Table `conversations`
- `closed_at` (TIMESTAMP)
- `is_urgent` (BOOLEAN DEFAULT FALSE)
- `meta_data` (JSONB DEFAULT '{}')

### Table `leads`
- `deleted_at` (TIMESTAMP)

## Recommendation for Future
The current database state works with the code, but Alembic might be confused about the schema state.
1. **Do not** delete the columns manually added.
2. Create a new migration using `alembic revision --autogenerate` *locally* with a properly connected environment to capture any other discrepancies, OR
3. If `alembic` tries to add these columns again, modify the migration script to use `op.add_column(..., if_not_exists=True)` or wrap in a try/except block.
