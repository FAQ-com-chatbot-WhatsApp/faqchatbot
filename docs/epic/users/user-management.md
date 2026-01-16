# User Management

## Scope
CRUD-style operations for users with blocking/deactivation and audit hooks.

## Operations (user_service)
- `list_users(include_inactive=False)`: returns active users unless flag set.
- `get_user(user_id)`: fetch by id.
- `update_user(user_id, **kwargs)`: updates mutable fields; raises `NotFoundException` if missing.
- `deactivate_user(user_id)`: sets `is_active=False`, flushes sessions via `session_service.revoke_all_user_sessions`, logs audit entry.
- `block_user(user_id, reason=None)`: sets `is_blocked=True`, revokes all sessions, writes audit with reason.
- `unblock_user(user_id)`: clears block flag, logs audit.

## Side Effects / Integrations
- Session revocation ensures blocked/deactivated users lose access tokens.
- Audit logging records admin actions for traceability.

## Notes
- Service relies on repositories and audit/session services; ensure DI wiring supplies them.
- No role changes documented here; extend update paths if role management is required.
