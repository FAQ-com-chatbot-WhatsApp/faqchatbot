# Lead Assignment & CRUD

## Purpose
Assign leads to users, manage lifecycle updates, and expose filtered retrieval paths.

## Operations
- `create_from_conversation(phone_number, name, email=None)`: idempotent create; reuses existing phone; initializes `maturity_score=0`.
- `assign_to_user(lead_id, user_id)`: sets `assigned_to_user_id`, logs success. Raises `NotFoundException` if lead missing.
- `convert(lead_id)`: sets `maturity_score=100` (conversion marker); requires lead exists.
- `mark_lost(lead_id, reason=None)`: resets `maturity_score=0`; logs reason; requires lead exists.
- `update_maturity(lead_id, new_score)`: validates 0-100; updates score; raises `BusinessRuleError` or `NotFoundException`.
- Listing helpers: `get_leads_by_status(status, limit)`, `get_unassigned_leads(limit)`, `list_leads(status, assigned_to_user_id, min_score, unassigned_only, limit, offset)` filter in memory from repo.

## Status / Constraints
- Status enum is `LeadStatus`; assignment does not change status automatically.
- No audit hooks here; upstream orchestrators should record audit if needed.
- Filtering is in-memory over `LeadRepository.get_all()`; watch for scalability.
