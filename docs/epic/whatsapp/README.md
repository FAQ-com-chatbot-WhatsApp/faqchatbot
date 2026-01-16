# Epic: WhatsApp Integration

**Status:** IMPLEMENTADO (Session Management + Message Sending + Rate Limiting)  
**Version:** 1.0  
**Owner:** Backend Team  
**Last Updated:** January 2026

## Overview

### Problem Statement

WhatsApp integration via WAHA (WhatsApp HTTP API) provides:
- Session management (pairing, QR codes, connection status)
- Message sending (text, image, file, location)
- Rate limiting (anti-ban protection)
- Webhook reception for inbound messages

### What's Implemented

✅ **Session Management:** 8 operations (waha_service.py:48-226)
✅ **Message Sending:** 5 operations with rate limiting (waha_service.py:275-367)
✅ **Rate Limiting:** Per-chat Redis-backed counter (waha_service.py:230-260)
✅ **QR Code Generation:** For phone pairing (waha_service.py:190-200)
✅ **Session Status Tracking:** INIT, STARTING, CONNECTED, LOADING, STOPPED, TIMEOUT

---

## Architecture & Actual Flows (What's Implemented)

### 1. Session Management

**Service:** `WAHAService` (waha_service.py)

**Data Model:**
- WhatsAppSession: id, name (unique), status, webhook_url, qr_code, connected_phone, created_at

#### create_session() - Lines 48-82
**Input:** SessionCreate(name, webhook_url_optional)
**Process:**
1. Check if session exists in DB by name
2. If exists: raise ValueError
3. Create session in WAHA API
4. Save to database
5. Return SessionOut (schema)

**Output:** SessionOut (id, name, status, webhook_url)

#### start_session() - Lines 83-113
**Input:** name: str
**Process:**
1. Lookup session in DB
2. Call WAHA to start session
3. Get QR code from WAHA
4. Update DB with status, QR code
5. Return SessionStatus (with QR)

**Output:** SessionStatus (status, qr_code, etc.)

#### stop_session() - Lines 115-139
**Input:** name: str
**Process:**
1. Lookup session in DB
2. Call WAHA to stop
3. Update DB status = "STOPPED"
4. Return result

**Output:** dict (success response)

#### restart_session() - Lines 141-166
**Input:** name: str
**Process:**
1. Lookup session
2. Call WAHA restart
3. Update DB status = "STARTING"
4. Return result

**Output:** dict

#### get_session_status() - Lines 162-189
**Input:** name: str
**Process:**
1. Lookup session in DB
2. Get fresh status from WAHA
3. If status changed: update DB
4. Return SessionStatus

**Output:** SessionStatus (status, connected_phone, me, etc.)

#### get_qr_code() - Lines 190-200
**Input:** name: str
**Process:**
1. Call WAHA get_qr_code()
2. Return base64 encoded image

**Output:** dict (qr_code image data)

#### logout_session() - Lines 201-225
**Input:** name: str
**Process:**
1. Logout from WhatsApp (unlink device)
2. Update DB status = "STOPPED", connected_phone = None
3. Return result

**Output:** dict

#### get_or_create_default_session() - Lines 227-237
**Input:** None (uses WAHA_SESSION_NAME from settings)
**Process:**
1. Lookup session by settings.WAHA_SESSION_NAME
2. If not found: create it
3. Return WhatsAppSession

**Output:** WhatsAppSession (default session)

---

### 2. Message Sending

**All message operations include rate limiting via `_check_rate_limit()`**

#### Rate Limiting - Lines 239-260
**Service:** `WAHAService._check_rate_limit()`

**Input:** chat_id: str

**Process:**
1. If no rate limit configured: return True (allow)
2. Get Redis key: f"waha:ratelimit:{chat_id}"
3. Get current count from Redis
4. If count >= WAHA_MESSAGES_PER_HOUR: return False (blocked)
5. Increment count + set expiry (3600s)
6. Return True

**Output:** bool (True = allowed, False = rate limited)

**Storage:** Redis (auto-expires after 1 hour)

---

#### send_text() - Lines 262-284
**Input:** SendTextRequest(chat_id, text, reply_to?, apply_anti_ban?)
**Process:**
1. Check rate limit
2. If blocked: raise ValueError
3. Call WAHA send_text with anti-ban setting
4. Return MessageSentResponse (id, timestamp, chat_id)

**Output:** MessageSentResponse

#### send_image() - Lines 286-306
**Input:** SendImageRequest(chat_id, file_url, caption?, apply_anti_ban?)
**Process:**
1. Check rate limit
2. Call WAHA send_image (URL-based, no upload)
3. Return MessageSentResponse

**Output:** MessageSentResponse

#### send_file() - Lines 308-328
**Input:** SendFileRequest(chat_id, file_url, filename, caption?)
**Process:**
1. Check rate limit
2. Call WAHA send_file
3. Return MessageSentResponse

**Output:** MessageSentResponse

#### send_location() - Lines 330-350
**Input:** SendLocationRequest(chat_id, latitude, longitude, title?)
**Process:**
1. Check rate limit
2. Call WAHA send_location
3. Return MessageSentResponse

**Output:** MessageSentResponse

#### send_seen() - Lines 352-367
**Input:** chat_id, message_id
**Process:**
1. Call WAHA send_seen (read receipt)
2. Return dict

**Output:** dict (success)

---

## Known Gaps & Limitations

### 1. Rate Limiting Configuration
- **Status:** IMPLEMENTED but CONFIGURABLE via settings
- **Current:** WAHA_MESSAGES_PER_HOUR (likely 10-20 per WhatsApp limits)
- **Missing:** Per-user limits, burst handling, SLA compliance
- **Priority:** LOW (works for MVP)

### 2. Session Persistence
- **Status:** BASIC
- **Current:** Saves session name + status to DB
- **Missing:** Session recovery on restart, automatic reconnection
- **Impact:** Manual restart required if bot crashes
- **Priority:** MEDIUM

### 3. Webhook Validation
- **Status:** MINIMAL
- **Current:** Assumes webhook signature validation exists
- **Missing:** No HMAC verification visible in code
- **Impact:** Potential spoofed webhook attacks
- **Priority:** HIGH

### 4. Message Acknowledgment
- **Status:** NO ACK on individual messages
- **Current:** send_text() returns immediately
- **Missing:** Delivery confirmation (WAHA callback tracking)
- **Impact:** No guarantee message was actually sent
- **Priority:** MEDIUM

### 5. Connection State Not Monitored
- **Status:** BASIC
- **Current:** Status reported from WAHA but no active health check
- **Missing:** Automatic reconnection, connection timeout handling
- **Priority:** MEDIUM

---

## Testing & Validation

### Tests Implemented
- ✅ Webhook reception (integration tests)
- ✅ Message sending (unit tests)
- ✅ Rate limiting (implied)

### Test Scenarios
1. ✅ Create session (QR code generation)
2. ✅ Start session (pair phone)
3. ✅ Get session status
4. ✅ Send text message
5. ✅ Send image (URL-based)
6. ✅ Send file
7. ✅ Send location
8. ✅ Rate limiting (blocks on threshold)
9. ⚠️ Session recovery on restart → NOT TESTED
10. ⚠️ Webhook signature validation → NOT TESTED
11. ⚠️ Message delivery confirmation → NOT TESTED

