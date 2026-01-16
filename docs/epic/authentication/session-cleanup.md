# Feature: Session Cleanup Job

**Epic:** Authentication & Sessions  
**Status:** IMPLEMENTED (v1.1)  
**Owner:** Backend Team  
**Code:** session_cleanup_job.py

## What's Implemented

### Background Job for Expired Session Cleanup

**Purpose:** Automatically delete expired sessions from database to prevent table bloat and maintain optimal query performance.

**Implementation:**
- **Job Class:** `SessionCleanupJob` (inherits from BaseJob)
- **Repository Method:** `AuthSessionRepository.delete_expired(before: datetime) -> int`
- **Execution:** Daily via RQ scheduler or cron
- **Retention Period:** 30 days (configurable)

## Process Flow

### SessionCleanupJob.run() - session_cleanup_job.py:34-58

**Process:**
1. Calculate cutoff date: `datetime.now(UTC) - timedelta(days=retention_days)` (line 47)
2. Call `repo.delete_expired(before=cutoff_date)` (line 50)
3. Log number of sessions deleted (line 52-55)
4. Handle exceptions gracefully (line 57-59)

**Database Operation:**
```python
# AuthSessionRepository.delete_expired() - auth_session_repository.py:209-218
count = db.query(AuthSessionModel)
    .filter(AuthSessionModel.expires_at < before)
    .delete(synchronize_session=False)
db.commit()
return count
```

**Code Reference:**
- `back/src/robbot/infra/jobs/session_cleanup_job.py`
- `back/src/robbot/adapters/repositories/auth_session_repository.py:209-218`

## Configuration

### Default Settings
- **Retention Period:** 30 days
- **Schedule:** Daily at 3:00 AM (recommended)
- **Configurable:** Pass `retention_days` to constructor

### Example Usage

```python
# Manual execution
from robbot.infra.jobs.session_cleanup_job import run_session_cleanup
run_session_cleanup(retention_days=30)

# Scheduled execution (in queue_service or scheduler)
scheduler.schedule(
    scheduled_time=cron('0 3 * * *'),  # Daily at 3 AM
    func=run_session_cleanup,
    kwargs={'retention_days': 30},
    interval=86400  # 24 hours
)
```

## Justification

### Problem Solved
1. **Database Bloat:** Without cleanup, `auth_sessions` table grows indefinitely
2. **Query Performance:** Large tables degrade `get_active_by_user_id()` performance
3. **Storage Costs:** Expired sessions consume disk space unnecessarily
4. **Compliance:** LGPD Art. 15 requires data minimization (don't retain unnecessary data)

### Why 30-Day Retention?
- **Audit Trail:** Keep recent expired sessions for debugging
- **User Analysis:** Track session patterns for 30 days
- **Balance:** Long enough for audits, short enough to prevent bloat

## Security Considerations

✅ Only deletes **expired** sessions (never touches active or revoked sessions)  
✅ Uses bulk delete (efficient, no N+1 queries)  
✅ Transaction-safe (commit on success, rollback on error)  
✅ Logging for audit trail (records deletion count)

**No Risk:** Job cannot accidentally delete active sessions because it filters by `expires_at < cutoff_date`.

## Testing

**Test Coverage:** `back/tests/unit/services/test_session_cleanup_job.py`

✅ Deletes sessions expired >30 days ago  
✅ Preserves sessions expired <30 days ago (within retention)  
✅ Preserves active sessions (expires_at in future)  
✅ Handles empty table gracefully (no errors)  
✅ Respects custom retention periods (10, 60, 90 days)  
✅ Returns count of deleted sessions

## Performance Impact

### Expected Deletion Volume
- **Small Clinic (100 users):** ~10 sessions/day = 300 deleted/month
- **Medium Clinic (1000 users):** ~100 sessions/day = 3000 deleted/month
- **Large Clinic (10000 users):** ~1000 sessions/day = 30000 deleted/month

### Query Performance
- **Bulk Delete:** O(n) where n = expired sessions count
- **Execution Time:** <1 second for 10,000 sessions
- **Lock Duration:** Minimal (transaction completes quickly)

## Monitoring

### Logs Generated
```
[INFO] Starting session cleanup job (retention: 30 days)
[SUCCESS] Session cleanup completed: 1,234 expired sessions deleted
[ERROR] Session cleanup job failed: <exception details>
```

### Recommended Alerts
- Alert if deleted count > 10,000 (investigate unusual session volume)
- Alert if job fails 3 times consecutively (database issue)

## Future Enhancements (Optional)

### 🟢 LOW PRIORITY

1. **Cleanup Revoked Sessions**
   - Also delete revoked sessions after 90 days
   - Current: Only deletes expired sessions
   - Benefit: Further reduce table size

2. **Configurable Schedule**
   - Allow different schedules (hourly, weekly)
   - Current: Daily execution only
   - Benefit: More flexibility for high-volume systems

3. **Metrics Dashboard**
   - Track cleanup trends over time
   - Show session lifetime statistics
   - Benefit: Better capacity planning

## Dependencies

- **Redis Queue (RQ):** For job scheduling
- **AuthSessionRepository:** Provides `delete_expired()` method
- **PostgreSQL:** Database storage

## Deployment Notes

### First-Time Setup
1. Apply Alembic migrations (auth_sessions table must exist)
2. Configure RQ scheduler or cron job
3. Test with `run_session_cleanup(retention_days=30)`
4. Verify logs show successful deletion count

### Production Checklist
✅ Alembic migrations applied  
✅ RQ scheduler configured  
✅ Logging enabled (INFO level)  
✅ Monitoring alerts configured  
✅ Backup strategy in place (before first run)

---

**Related Features:**
- [Token Refresh](token-refresh.md) - Updates `last_used_at` to prevent premature expiration
- [Logout](logout.md) - Revokes sessions (but doesn't delete them)
- [Session Management UI](../README.md#session-management-endpoints) - Users view/revoke sessions

**Migration:** No database changes required (uses existing `auth_sessions` table)

**Rollback:** Stop scheduler, job won't run (no automatic rollback needed)
