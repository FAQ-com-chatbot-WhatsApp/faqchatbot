# WAHA LID Resolution - Investigation Plan & Solution Architecture

**Status**: Investigation Complete | Ready for Implementation Planning
**Date**: 2025-02-14
**Priority**: High - Impacts lead CRM data integrity
**Evidence Source**: Official WAHA Documentation (https://waha.devlike.pro/docs/how-to/contacts/)

---

## 1. Problem Statement

### Current Issue
When WhatsApp customers send messages with @lid format (Lightweight ID), the system extracts the LID as the phone number instead of the real phone number. This causes:

- ❌ **CRM Data Corruption**: Lead `phone_number` field stores LID (e.g., `24988337893388`) instead of real phone number (e.g., `555191628223`)
- ✅ **Service Continuity**: Bot DOES respond and customer IS served (no UX impact)
- 🔴 **Business Impact**: CRM loses phone number, cannot contact customer back, reporting is incorrect

### Message Flow
```
WhatsApp WAHA → Webhook receives: {"from": "24988337893388@lid"}
                              ↓
              Webhook extracts: phone = "24988337893388"
                              ↓
              Message job passes to orchestrator: phone_number = "24988337893388"
                              ↓
              Lead created: LeadModel(phone_number="24988337893388", ...)
                              ↓
              Result: CRM has LID instead of real phone ❌
```

---

## 2. Root Cause Analysis

### Why Both @c.us and @lid Exist (Official WAHA Documentation)

**From WAHA Docs (https://waha.devlike.pro/docs/how-to/contacts/):**

> "WhatsApp finalized its LID (Local Identifier) update (which it started in 2023). This LID system assures the anonymity of users in large groups, allowing the WhatsApp client to show a masked value instead of a full phone number. This is done to ensure the privacy of users."

**Two Identifier Formats:**
- `123123123@c.us` = **Real phone number format** (standard, user-facing)
- `123123123@lid` = **Hidden user ID format** (privacy protection, WhatsApp internal)

**Key Quote from WAHA:**
> "You can message anyone using either their LID or their PN. Use the API - Lids to map between a Phone Number (@c.us) and a LID (@lid) in both directions."

### When @lid Arrives

WAHA returns @lid format in these scenarios:
1. **Group messages** - To protect user privacy in large groups
2. **New/unsynced contacts** - If contact list hasn't been refreshed
3. **Private groups** - Depending on group settings

Current system ACCEPTS both formats but **doesn't resolve** @lid to @c.us.

---

## 3. Official WAHA Solution: LID Resolution API

### Available Endpoints (All Base WAHA, Not Plus-Only)

#### 3.1 Get Phone Number by LID
```
GET /api/{session}/lids/{lid}

URL Parameter:
- {session}: Session name (e.g., "default")
- {lid}: The LID identifier (e.g., "24988337893388@lid")

Response:
{
  "lid": "123123123@lid",
  "pn": "123456789@c.us"
}
```

#### 3.2 Get LID by Phone Number  
```
GET /api/{session}/lids/pn/{phoneNumber}

URL Parameter:
- {session}: Session name (e.g., "default")
- {phoneNumber}: Phone number (e.g., "123456789" or "123456789@c.us")

Response:
{
  "lid": "123123123@lid",
  "pn": "123456789@c.us"
}
```

#### 3.3 Get All Known LID Mappings
```
GET /api/{session}/lids?limit=100&offset=0

Query Parameters:
- limit: Number of records (default: 100)
- offset: Pagination offset (default: 0)

Response:
[
  {
    "lid": "123123123@lid",
    "pn": "123456789@c.us"
  },
  ...
]
```

### Important: Contact List Constraint

**From WAHA FAQ:**
> "If you don't find a phone number by lid - you don't have the phone number in your contact list or you're not admin in the group."

This means:
- ✅ LID resolution ONLY works if contact exists in session's contact list
- ✅ For group messages, bot must be admin to resolve participant LIDs
- ⚠️ Fallback behavior needed when contact not found

---

## 4. Current Implementation Status

### What's Implemented
- ✅ webhook_controller.py accepts messages with @lid format (no blocking)
- ✅ message_job.py extracts phone from both @c.us and @lid
- ✅ orchestrator creates conversations with any identifier
- ✅ Customer receives response (service works)
- ✅ DEV_MODE has partial LID resolution (polling side only)

### What's Missing
- ❌ No LID resolution methods in waha_client.py
- ❌ No @lid → @c.us conversion in production webhook flow
- ❌ No validation before lead creation

### Code Inventory

**File**: `back/src/robbot/adapters/external/waha_client.py`
- Current Methods: 63 total (sessions, messages, contacts, media, etc.)
- **Missing Methods**: 
  - `async def get_phone_by_lid(session: str, lid: str)` → `/api/{session}/lids/{lid}`
  - `async def get_lid_by_phone(session: str, phone: str)` → `/api/{session}/lids/pn/{phone}`
  - `async def get_all_lids(session: str, limit: int, offset: int)` → `/api/{session}/lids`

---

## 5. Proposed Solution Architecture

### Recommended Implementation: Two-Tier Webhook Validation

**Goal**: Ensure lead.phone_number always has real phone (@c.us), never LID

#### Strategy A: Eager Resolution (Recommended)
```
Webhook receives message with @lid
                    ↓
         Attempt immediate resolution via WAHA API
                    ↓
         Success: Extract phone_number from response
         Failure: Use original LID, log warning
                    ↓
         Pass resolved phone_number to orchestrator
                    ↓
         Lead created with real phone number ✅
```

**Pros:**
- Resolves at entry point (cleanest flow)
- Phone number guaranteed in lead CRM
- Works for known contacts

**Cons:**
- Adds HTTP request latency to webhook processing
- Fails gracefully for unknown contacts (logs warning)

#### Strategy B: Lazy Resolution (Fallback)
```
If webhook has @lid:
  1. Try immediate resolution (cached from contacts endpoint)
  2. If fails: Accept @lid, let orchestrator validate
  3. Orchestrator logs warning and creates lead with @lid
  4. Background job attempts resolution later
```

**Pros:**
- Lower webhook latency
- Graceful degradation

**Cons:**
- CRM might still get corrupted data temporarily
- More complex logic

### Recommendation: **Hybrid Approach**
1. **Webhook** (Primary): Fast resolution attempt with timeout (500ms max)
2. **Orchestrator** (Validation): Fallback check if @lid detected
3. **Background Job** (Cleanup): Resolve missed @lids after the fact

---

## 6. Acceptance Criteria

### Must-Have
- ✅ When @lid arrives, phone_number field in lead contains real @c.us format
- ✅ Or: phone_number contains just the number (no @ format) that can be used
- ✅ System handles contact list constraint (graceful when contact not found)
- ✅ Solution uses only official WAHA LID APIs

### Should-Have
- ✅ Logging of LID resolution attempts (success/failure)
- ✅ Cache LID→phone mappings to reduce API calls
- ✅ Handle Brazilian phone number format edge cases (9-digit addition)

### Nice-to-Have
- ✅ Metrics on @lid arrival frequency
- ✅ Admin dashboard showing unresolved LIDs

---

## 7. Implementation Options (No Code, Planning Only)

### Option 1: Webhook Controller Enhancement
**Location**: `back/src/robbot/adapters/controllers/webhook_controller.py` lines 64-124

**Changes Needed**:
1. After extracting `phone = chat_id.split("@")[0]`
2. Check if format is @lid: `if "@lid" in chat_id`
3. If @lid detected:
   - Call new waha_client method: `get_phone_by_lid(session, chat_id)`
   - Extract phone from response: `phone = response.get("pn", "").split("@")[0]`
   - Log resolution: `logger.info(f"Resolved LID {chat_id} to phone {phone}")`
4. Pass resolved phone to message job

**Effort**: Low (add 5-10 lines + new waha_client methods)

### Option 2: Message Job Enhancement
**Location**: `back/src/robbot/infra/jobs/message_job.py` lines 125-165

**Changes Needed**:
1. Check if phone format is @lid before calling orchestrator
2. If @lid, attempt resolution before processing
3. Add caching of successful resolutions (Redis)
4. Fallback to original phone if resolution fails

**Effort**: Medium (requires caching logic)

### Option 3: Orchestrator Validation
**Location**: `back/src/robbot/services/conversation_orchestrator.py` lines 446-478

**Changes Needed**:
1. Before creating lead, validate phone_number format
2. If @lid detected, attempt last-chance resolution
3. Create lead with best-effort phone_number
4. Log warnings for unresolved IDs

**Effort**: Low (validation layer only)

---

## 8. Missing Implementation Details (For Code Phase)

### New Methods Needed in `waha_client.py`

```python
# Method 1: Get phone number from LID
async def get_phone_by_lid(
    self, 
    session: str, 
    lid: str
) -> dict[str, Any] | None:
    """
    Resolve WhatsApp LID to phone number.
    
    Args:
        session: Session name (e.g., 'default')
        lid: LID identifier (e.g., '24988337893388@lid' or '24988337893388')
    
    Returns:
        {"lid": "123@lid", "pn": "123456789@c.us"} or None if not found
    
    Raises:
        WAHAError: On API errors
    """
    # Implementation: GET /api/{session}/lids/{lid}
    # Escape @ symbol: 24988337893388@lid → 24988337893388%40lid
    # Return response or None if 404
```

```python
# Method 2: Get LID from phone number (less critical, for reference)
async def get_lid_by_phone(
    self,
    session: str,
    phone: str
) -> dict[str, Any] | None:
    """
    Resolve phone number to WhatsApp LID.
    
    Args:
        session: Session name
        phone: Phone number (e.g., '123456789' or '123456789@c.us')
    
    Returns:
        {"lid": "123@lid", "pn": "123456789@c.us"} or None if not found
    """
    # Implementation: GET /api/{session}/lids/pn/{phone}
    # Escape @ symbol if present
```

### Configuration Needed
- Optional: Add Redis cache for LID→phone mappings (24-hour TTL)
- Optional: Add timeout setting for LID resolution (default: 500ms)
- Optional: Add logging level for resolution attempts

### Error Handling Strategy
1. **Resolution Success**: Use phone_number from response
2. **Contact Not Found (404)**: Log warning, continue with original LID
3. **API Timeout**: Log error, continue with original LID
4. **API Error (5xx)**: Log error, continue with original LID
5. **Result**: Message is ALWAYS processed, CRM gets best-effort phone_number

---

## 9. Testing Strategy (Outlined, No Code)

### Test Scenarios Needed

**Test 1: Direct @lid Webhook**
- Input: Message webhook with `from: "24988337893388@lid"`
- Expected: Phone resolved to real number before lead creation
- Verify: Lead created with resolved phone, not LID

**Test 2: Contact Not in List**
- Input: Message from unknown contact as @lid
- Expected: Graceful fallback, log warning
- Verify: Message processed, warning logged, lead created with original LID

**Test 3: Mixed Format Messages**
- Input: Sequence of @c.us and @lid messages
- Expected: Both processed correctly
- Verify: Lead CRM has correct phone_numbers

**Test 4: Cache Performance**
- Input: Repeated @lid from same contact
- Expected: Second resolution uses cache
- Verify: WAHA API called once, cached for 24h

**Test 5: Brazilian Phone Format**
- Input: Brazilian number as @lid (pre-2012 and post-2012)
- Expected: Correct normalization
- Verify: Phone field matches expected format

---

## 10. Summary & Decision

### What We Learned (Definitive)
✅ WAHA **officially provides** LID resolution via official API
✅ No special licensing needed (base WAHA feature)
✅ Contact list constraint is documented and expected
✅ Current system works for customer service but fails for CRM data integrity

### What We Can Build (Planned)
✅ Add 2-3 new methods to waha_client.py (official endpoints)
✅ Enhance webhook_controller.py with resolution logic
✅ Add validation in orchestrator as safety net
✅ Implement caching for performance

### Why This Is The Right Solution
✅ Evidence-based (not guesswork): WAHA docs explicitly say "map between LID and PN"
✅ Low-risk: Resolution is attempt-once, graceful fallback if contact unknown
✅ Proven: DEV_MODE already uses same `/lids/pn/{phone}` endpoint successfully
✅ Maintainable: Small addition to existing client pattern

---

## Next Phase: Implementation

**Ready for**: Code review before implementation
**Dependencies**: None - all WAHA APIs already available
**Estimated Effort**: 4-6 hours (add methods, integrate, test)
**Risk Level**: Low (graceful fallback, no breaking changes)

### Files to Modify
1. `back/src/robbot/adapters/external/waha_client.py` - Add 2 methods
2. `back/src/robbot/adapters/controllers/webhook_controller.py` - Add resolution logic
3. `back/src/robbot/services/conversation_orchestrator.py` - Add validation
4. `back/tests/` - Add test cases for @lid scenarios

---

**Document Status**: Complete Investigation, Ready for Architecture Review
**Author**: Investigation via WAHA Official Documentation
**Confidence**: 100% (based on official WAHA source, not assumptions)
