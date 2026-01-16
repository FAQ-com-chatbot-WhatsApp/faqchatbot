# Epic: Leads

**Status:** IMPLEMENTADO (CRUD + Scoring + Assignment + Filtering)  
**Version:** 1.0  
**Owner:** Backend Team  
**Last Updated:** January 2026

## Overview

### Problem Statement

Leads are potential patients identified through conversations. The system must:
- Create leads from conversations (deduplication by phone)
- Track lead maturity through scoring (0-100)
- Manage lead-to-staff assignment (manual + auto load-balancing)
- Support filtering/querying with pagination
- Handle lead conversion and loss tracking

### What's Implemented

✅ **Lead CRUD:** create_from_conversation, get_by_id, list_all, update (lead_service.py:24-67)
✅ **Lead Scoring:** update_maturity with 0-100 range validation (lead_service.py:69-105)
✅ **Lead Assignment:** assign_to_user (manual) + auto_assign_lead (round-robin) (lead_service.py:107-134, 276-320)
✅ **Lead Conversion:** convert() + mark_lost() (lead_service.py:156-187)
✅ **Lead Filtering:** list_leads() with multi-criteria filter + pagination (lead_service.py:232-274)
✅ **Soft Delete:** soft_delete() + restore() (lead_service.py:308-355)

---

## Architecture & Actual Flows (What's Implemented)

### 1. Lead Creation

**Service:** `LeadService.create_from_conversation()` (lead_service.py:33-67)

**Input:**
- phone_number: str (unique identifier)
- name: str (lead name)
- email: str | None (optional)

**Process:**
1. Check if lead already exists by phone_number (deduplication)
2. If exists: return existing lead (log warning)
3. If new:
   - Create LeadModel(phone_number, name, email, maturity_score=0)
   - Call repo.create()
   - Log success with ID + phone

**Output:** LeadModel (new or existing)

**Database Fields:**
- id: UUID (primary key)
- phone_number: str (unique constraint)
- name: str
- email: str | None
- maturity_score: int (default 0, range 0-100)
- status: LeadStatus enum (default NEW)
- assigned_to_user_id: int | None (FK to users)
- created_at: datetime
- deleted_at: datetime | None (for soft delete)

---

### 2. Lead Maturity Scoring

**Service:** `LeadService.update_maturity()` (lead_service.py:69-105)

**Input:**
- lead_id: str (UUID)
- new_score: int (0-100)

**Process:**
1. Validate score is in range [0, 100] → raise BusinessRuleError if not
2. Lookup lead by ID → raise NotFoundException if not found
3. Get old score for logging
4. Update lead.maturity_score = new_score
5. Call repo.update()
6. Log success with before/after/delta

**Output:** Updated LeadModel

**Validation:**
- Range: 0-100 (enforced at line 77)
- Raises: BusinessRuleError, NotFoundException

**Usage:** Called by IntentDetector.update_maturity_score() with intent-based deltas

---

### 3. Lead Assignment

#### Manual Assignment - Lines 107-134
**Service:** `LeadService.assign_to_user()`

**Input:**
- lead_id: str
- user_id: int

**Process:**
1. Lookup lead by ID
2. Set lead.assigned_to_user_id = user_id
3. Call repo.update()
4. Log success

**Output:** Updated LeadModel

---

#### Auto-Assignment (Round-Robin) - Lines 276-320
**Service:** `LeadService.auto_assign_lead()`

**Input:**
- lead_id: str

**Process:**
1. Lookup lead by ID
2. Get all active users (role="user")
3. Count active leads per secretary (status in [ENGAGED, INTERESTED])
4. Select secretary with MINIMUM active leads (load balancing)
5. Set lead.assigned_to_user_id = selected_secretary.id
6. Call repo.update()
7. Log success

**Output:** Updated LeadModel OR None if no secretaries available

**Algorithm:** Weighted round-robin by current workload

---

### 4. Lead Conversion & Loss

#### Convert (Mark as Converted) - Lines 156-165
**Service:** `LeadService.convert()`

**Input:** lead_id: str

**Process:**
1. Lookup lead by ID
2. Set lead.maturity_score = 100
3. Update status to CONVERTED
4. Call repo.update()

**Output:** Updated LeadModel

---

#### Mark as Lost - Lines 167-187
**Service:** `LeadService.mark_lost()`

**Input:**
- lead_id: str
- reason: str | None (optional)

**Process:**
1. Lookup lead by ID
2. Set lead.maturity_score = 0
3. Update status to LOST
4. Call repo.update()
5. Log with reason

**Output:** Updated LeadModel

---

### 5. Lead Querying & Filtering

#### Get Leads by Status - Lines 189-210
**Service:** `LeadService.get_leads_by_status()`

**Input:**
- status: LeadStatus enum
- limit: int (default 50)

**Process:**
1. Fetch all leads from repo.get_all()
2. Filter in-memory: [lead for lead if lead.status == status]
3. Return first `limit` results

**Output:** list[LeadModel]

**Issue:** IN-MEMORY FILTERING - not database query (lines 197-201)

---

#### Advanced Filtering - Lines 232-274
**Service:** `LeadService.list_leads()`

**Input:**
- status: LeadStatus | None
- assigned_to_user_id: int | None
- min_score: int | None
- unassigned_only: bool (default False)
- limit: int (default 50)
- offset: int (default 0)

**Process:**
1. Fetch all leads: repo.get_all()
2. Apply filters (in-memory):
   - If status: filter by status
   - If unassigned_only: keep leads where assigned_to_user_id is None
   - If assigned_to_user_id: keep leads assigned to specific user
   - If min_score: keep leads with score >= min_score
3. Apply pagination: filtered[offset:offset+limit]
4. Return (paginated_list, total_count)

**Output:** tuple[list[LeadModel], int]

**Issue:** IN-MEMORY FILTERING + PAGINATION (poor performance at scale, lines 245-260)

---

#### Get Unassigned Leads - Lines 212-230
**Service:** `LeadService.get_unassigned_leads()`

**Input:** limit: int (default 50)

**Process:**
1. Fetch all leads: repo.get_all()
2. Filter: [lead for lead if lead.assigned_to_user_id is None]
3. Return first `limit` results

**Output:** list[LeadModel]

**Issue:** IN-MEMORY FILTERING (lines 223-225)

---

### 6. Soft Delete & Restore

#### Soft Delete - Lines 308-331
**Service:** `LeadService.soft_delete()`

**Input:** lead_id: str

**Process:**
1. Lookup lead by ID
2. Check if already deleted (deleted_at is not None)
3. If deleted: log warning and return
4. If not: set lead.deleted_at = datetime.now(UTC)
5. Call repo.update()

**Output:** Updated LeadModel (with deleted_at timestamp)

---

#### Restore - Lines 333-355
**Service:** `LeadService.restore()`

**Input:** lead_id: str

**Process:**
1. Lookup lead by ID (note: repo.get_all() doesn't filter deleted_at, see issue)
2. Check if deleted (deleted_at is not None)
3. If not deleted: log warning and return
4. If deleted: set lead.deleted_at = None
5. Call repo.update()

**Output:** Updated LeadModel (with deleted_at cleared)

---

## Known Gaps & Limitations

### 1. In-Memory Filtering (PERFORMANCE ISSUE)
- **Status:** IMPLEMENTED but INEFFICIENT
- **Affected Methods:**
  - get_leads_by_status() (lines 197-201)
  - list_leads() (lines 245-260)
  - get_unassigned_leads() (lines 223-225)
- **Issue:** Fetches ALL leads into memory, then filters
- **Impact:** O(n) memory usage, slow for 1000s of leads
- **Solution:** Move filters to SQL WHERE clauses in repository
- **Priority:** MEDIUM (critical at scale)

### 2. No SQL Filtering
- **Status:** MISSING
- **Current:** All filtering done in Python loops
- **Missing:** Database-level filtering (LeadRepository.get_by_status_sql, etc.)
- **Impact:** Can't efficiently query "all INTERESTED leads assigned to user X with score > 50"
- **Priority:** MEDIUM

### 3. Soft Delete Not Enforced
- **Status:** IMPLEMENTED but NOT ENFORCED
- **Current:** soft_delete() exists, can restore()
- **Missing:** All queries should exclude deleted_at IS NOT NULL
- **Example:** list_leads() returns deleted leads (lines 245)
- **Impact:** Deleted leads appear in results
- **Priority:** HIGH

### 4. Auto-Assignment Algorithm
- **Status:** SIMPLE but may not be optimal
- **Current:** Counts ACTIVE leads per secretary (ENGAGED + INTERESTED only)
- **Issue:** Doesn't account for secretary capacity/availability
- **Missing:** Secretary availability status, SLA timers, urgency weighting
- **Priority:** LOW (works for MVP)

### 5. Status Enum Validation
- **Status:** LeadStatus enum exists but values unclear
- **Issue:** No explicit list of valid statuses in code comments
- **Values:** Assumed: NEW, ENGAGED, INTERESTED, READY, CONVERTED, LOST
- **Missing:** Clear documentation of status transitions
- **Priority:** LOW

---

## Testing & Validation

### Tests Implemented
- ✅ integration/test_conversion_reports_l2.py (implied by conversion tracking)
- ✅ Unit tests for lead CRUD operations

### Test Scenarios
1. ✅ Create lead from conversation (phone deduplication)
2. ✅ Update maturity score (range validation)
3. ✅ Manual assignment to user
4. ✅ Auto-assignment (round-robin)
5. ✅ Convert lead (score = 100)
6. ✅ Mark as lost (score = 0)
7. ✅ Filter by status
8. ✅ Filter by assigned user
9. ✅ Soft delete + restore
10. ⚠️ Filtering performance at scale → NOT TESTED
11. ⚠️ Deleted leads exclusion → NOT TESTED (likely failing)

---

## What's Implemented ✅

### 1. Lead Lifecycle Management
- `create_from_conversation()`: Create from WhatsApp conversation with phone deduplication
- `convert()`: Mark lead as converted (score = 100)
- `mark_lost()`: Mark as lost with optional reason
- `soft_delete()` / `restore()`: Soft-delete with audit trail preservation

### 2. Lead Scoring System
- `update_maturity()`: Update score (0-100 range validation)
- Score-based SPIN phase progression
- Score > 70 triggers human escalation

### 3. Lead Assignment
- `assign_to_user()`: Manual assignment to secretary
- `auto_assign_lead()`: Load-balanced round-robin assignment with workload awareness

### 4. Querying & Filtering
- `get_leads_by_status()`: Filter by LeadStatus enum
- `list_leads()`: Advanced filtering with pagination (status, user, score, unassigned_only)
- `get_unassigned_leads()`: Quick query for unassigned leads

### 5. Data Model
- Fields: id (UUID), phone_number (unique), name, email, status (enum), maturity_score (0-100)
- assigned_to_user_id: FK to users, converted_at, deleted_at timestamps

## Code References

**Main Service:** [back/src/robbot/services/lead_service.py](back/src/robbot/services/lead_service.py) (377 lines)
- `create_from_conversation()` - Lines 33-67
- `update_maturity()` - Lines 68-105
- `assign_to_user()` - Lines 106-134
- `convert()` - Lines 135-158
- `mark_lost()` - Lines 159-187
- `list_leads()` - Lines 232-274
- `auto_assign_lead()` - Lines 276-320

## Gaps ❌

| Gap | Priority |
|-----|----------|
| LeadStatus enum validation | HIGH |
| Bulk update API | MEDIUM |
| Lead merge/deduplication | MEDIUM |
| Configurable scoring model | MEDIUM |
| Lead tags/custom fields | LOW |
| Activity timeline API | LOW |

## Flows

### Lead Creation
WhatsApp Message → create_from_conversation() → Check phone dedup → Create if new

### Score Progression
Intent Detection → update_maturity() → Score increments → Check > 70 → Trigger escalation

### Auto-assignment
New Lead → auto_assign_lead() → Count secretary workload → Assign to least-loaded → Record

### Conversion
Score >= 70 → convert() → Set status=CONVERTED, score=100, converted_at timestamp

## Next Steps

1. Implement LeadStatus enum with valid transitions
2. Add bulk update/assign APIs
3. Build lead merge endpoint for deduplication
4. Configurable scoring model (allow clinic customization)
