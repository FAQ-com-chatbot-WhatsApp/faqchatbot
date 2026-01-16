# Audit Logging

## Scope
Capture CRUD and generic actions with before/after snapshots for compliance and traceability.

## Service Behavior (audit_service)
- `log_action(user_id, action, entity, entity_id=None, details=None, old_values=None, new_values=None)`: base logger storing JSON-serialized payload.
- Shortcuts: `log_create`, `log_update`, `log_delete` call `log_action` with presets; update includes `old_values` and `new_values`.
- Query helpers: `get_user_logs(user_id, limit=50)`, `get_entity_logs(entity, entity_id, limit=50)`, `get_recent_logs(limit=100)` from repository.

## Data Model
- Persists via `AuditLogRepository`; fields include action, entity name, entity_id, user_id, details blob, and JSON snapshots of old/new values when provided.

## Notes / Gaps
- Caller is responsible for passing sanitized old/new values (serializable dicts).
- No PII redaction in service; ensure sensitive data is scrubbed upstream.
- Error handling logs and rethrows; callers should handle repository errors.
