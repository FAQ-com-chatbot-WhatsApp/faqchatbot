# Feature: Send WhatsApp Message

**Epic:** WhatsApp Integration  
**Status:** MVP Complete (Core Implemented)  
**Owner:** Backend & Integration Team  
**Implementation:** 374 lines (waha_service.py)  
**Tests:** [back/tests/unit/services/test_waha_service.py](back/tests/unit/services/test_waha_service.py)

## What's Implemented ✅

### 1. Message Sending Core
- **send_text()**: Send text message via WAHA API
  - Accept: SendTextRequest (chat_id, text, reply_to optional, apply_anti_ban flag)
  - Check rate limit before sending
  - Call WAHA API with parameters
  - Extract message_id, timestamp from response
  - Return MessageSentResponse
  - Log delivery status

### 2. Rate Limiting (Anti-Ban Protection)
- **_check_rate_limit()**: Enforce message throttling
  - Query Redis counter: messages_per_hour:{chat_id}
  - Compare against setting: WAHA_MESSAGES_PER_HOUR (default 10/hour)
  - Increment counter on success
  - Set expiration: 3600 seconds (1 hour)
  - Returns: boolean (allow/deny)
  - Raises ValueError if limit exceeded

### 3. Anti-Ban Protection
- **apply_anti_ban flag**: Reduce ban risk
  - When enabled: Add random delay (100-500ms)
  - When enabled: Add typing indicator before send
  - When enabled: Variable character delays
  - Only sent if WAHA_ANTI_BAN_ENABLED = true (config-based)
  - Mimics human behavior (prevents WhatsApp detection)

### 4. Message Delivery Tracking
- Extract from WAHA response:
  - message_id: Unique identifier for the message
  - timestamp: Unix timestamp of send time
  - chat_id: Echo back for verification
- Store in ConversationMessage:
  - direction: "outbound"
  - external_message_id: message_id from WAHA
  - sent_at: timestamp
  - delivery_status: pending (until webhook confirms)

### 5. Reply Threading
- **message_id_to_reply** parameter (optional)
- Links new message to previous one
- Creates conversation thread
- User sees context in WhatsApp UI

### 6. Error Handling
- Rate limit exceeded → ValueError
- WAHA API error → WhatsAppError
- Network timeout → RetryableError (RQ retries)
- Invalid chat_id → ValueError

### 7. Logging
- Info level: Message sent successfully
- Warning level: Rate limit warnings
- Error level: API failures

## Code References

**Main Method:** [back/src/robbot/services/waha_service.py](back/src/robbot/services/waha_service.py#L275-L298) (lines 275-298)
- Input: SendTextRequest {chat_id, text, reply_to, apply_anti_ban}
- Output: MessageSentResponse {message_id, timestamp, chat_id}
- Raises: ValueError (rate limit), WhatsAppError (API error)

**Rate Limit Check:** [back/src/robbot/services/waha_service.py](back/src/robbot/services/waha_service.py) (lines ~200-230)
- `_check_rate_limit(chat_id)` - Async Redis check
- Redis key: f"messages_per_hour:{chat_id}"
- TTL: 3600 seconds
- Threshold: settings.WAHA_MESSAGES_PER_HOUR

**Anti-Ban Utilities:** [back/src/robbot/services/waha_service.py](back/src/robbot/services/waha_service.py) (lines ~150-199)
- `_apply_human_like_delay()` - Random 100-500ms wait
- `_send_typing_indicator()` - Show "typing..." state
- `_vary_send_speed()` - Variable character delays

**Models:**
- [SendTextRequest](back/src/robbot/schemas/waha.py)
  - chat_id: str (WhatsApp chat ID)
  - text: str (message content, max 4096 chars)
  - reply_to: str (optional, message_id to reply to)
  - apply_anti_ban: bool (default true)

- [MessageSentResponse](back/src/robbot/schemas/waha.py)
  - message_id: str (WAHA response ID)
  - timestamp: int (Unix timestamp)
  - chat_id: str (echo)

**WAHA Client:**
- [back/src/robbot/infra/external/waha_client.py](back/src/robbot/infra/external/waha_client.py)
  - `send_text()` - HTTP POST to WAHA API
  - Endpoint: https://waha-server/api/sendText
  - Auth: Bearer token (stored in settings)

**Redis Configuration:**
- [back/src/robbot/infra/redis/redis_manager.py](back/src/robbot/infra/redis/redis_manager.py)
  - Connection pool for rate limiting
  - Get current count per chat_id

## Gaps ❌

| Gap | Priority | Impact |
|-----|----------|--------|
| Message delivery confirmation | HIGH | Cannot track if user actually received |
| Message read status | MEDIUM | Don't know if user read message |
| Message retry on failure | MEDIUM | Failed messages not retried |
| Bulk message sending | MEDIUM | Cannot send to multiple users efficiently |
| Message scheduling | LOW | Cannot send message at future time |

## Validation Rules

```
Chat ID:
  - WhatsApp format: "5511987654321@c.us" or similar
  - Must be valid phone number format
  - Required

Text Content:
  - Maximum 4096 characters (WhatsApp limit)
  - No null bytes
  - Unicode support (emojis okay)
  - Required

Rate Limit:
  - Per chat_id (user-specific)
  - Default: 10 messages/hour
  - Prevents abuse + ban risk

Anti-Ban:
  - Optional (config-based)
  - Adds delay if enabled
  - Transparent to caller

Reply To:
  - Must be valid message_id from previous message
  - Optional
  - Creates thread if provided
```

## Flow Diagram - Text Message Send

```
POST /api/v1/messages/send
{
  "chat_id": "5511987654321@c.us",
  "text": "Hello Maria! How can I help you today?",
  "apply_anti_ban": true,
  "reply_to": null
}
  ↓
validate_request()
  - chat_id format valid? ✓
  - text length <= 4096? ✓
  - text not empty? ✓
  ↓
Check rate limit:
  Redis key: "messages_per_hour:5511987654321@c.us"
  Current count: 3 (out of 10)
  Allowed? YES
  ↓
Apply anti-ban (optional):
  - Send typing indicator (simulates human)
  - Random delay: 234ms
  - Variable character delays
  ↓
Call WAHA API:
  POST https://waha-server/api/sendText
  {
    "session": "default",
    "chat_id": "5511987654321@c.us",
    "text": "Hello Maria!...",
    "apply_anti_ban": true
  }
  ↓
WAHA Response:
  {
    "id": "msg_abc123",
    "timestamp": 1704067200
  }
  ↓
Increment rate limit counter:
  Redis: "messages_per_hour:..." = 4
  Set expiration: 3600 seconds
  ↓
Log: "[INFO] Text message sent to 5511987654321@c.us"
  ↓
Save to ConversationMessage:
  - direction: "outbound"
  - external_message_id: "msg_abc123"
  - sent_at: 1704067200
  - delivery_status: "pending"
  ↓
Return 200 OK:
{
  "message_id": "msg_abc123",
  "timestamp": 1704067200,
  "chat_id": "5511987654321@c.us"
}
```

## Flow Diagram - Rate Limit Exceeded

```
POST /api/v1/messages/send
  (11th message in same hour)
  ↓
Check rate limit:
  Redis: "messages_per_hour:..." = 10 (max)
  Allowed? NO
  ↓
Raise ValueError:
  "Rate limit exceeded: max 10 msg/hour"
  ↓
Return 429 Too Many Requests:
{
  "error": "Rate limit exceeded",
  "limit": 10,
  "window": "3600 seconds",
  "reset_at": "2024-01-01T14:00:00Z"
}
  ↓
Client should retry after reset_at
```

## Integration Points

**Called by:**
- ConversationOrchestrator.process_inbound_message() (Step 15)
- Manual message sending via API endpoint

**Calls:**
- WAHAClient (HTTP to WAHA server)
- RedisManager (rate limiting)
- ConversationMessageRepository (store sent message)

**Called before this:**
- Message generation (Gemini API creates content)
- Intent detection (determines message strategy)

## Testing Strategy

### Unit Tests

```python
def test_send_text_succeeds():
    """Valid message sent successfully"""
    
def test_send_text_rate_limited():
    """Rate limit exceeded raises ValueError"""
    
def test_send_text_increments_counter():
    """Redis counter incremented after send"""
    
def test_send_text_applies_anti_ban():
    """Anti-ban delays applied when enabled"""
    
def test_send_text_handles_waha_error():
    """WAHA API error handled gracefully"""
    
def test_send_text_validates_chat_id():
    """Invalid chat_id rejected"""
    
def test_send_text_truncates_long_message():
    """Messages > 4096 chars truncated"""
    
def test_send_text_reply_threading():
    """reply_to parameter creates thread"""
```

### Integration Tests

```python
def test_send_text_rate_limit_window():
    """Rate limit resets after 1 hour"""
    
def test_send_text_complete_flow():
    """Message sent → stored → confirmed"""
```

## Security Considerations

1. **Rate Limiting**: Prevents message flooding (ban risk)
2. **Anti-Ban**: Mimics human behavior (WhatsApp detection evasion)
3. **Authorization**: Only authenticated sessions can send
4. **PII Protection**: Patient data not logged (only message ID)
5. **Audit Trail**: All sends logged with timestamp
6. **Input Sanitization**: Text cleaned before sending (no injection)

## Performance Considerations

**Message Delivery Time:**
- Target: < 500ms (feel instant to user)
- Current: ~200-300ms (API + delay)
- Anti-ban adds 100-500ms (acceptable)

**Rate Limiting:**
- Redis lookup: ~1ms
- Counter increment: ~1ms
- Total overhead: Minimal

**Scaling:**
- Rate limit per chat_id (distributed)
- No centralized bottleneck
- Horizontal scaling via Redis sharding

## Next Steps (Roadmap)

### High Priority
1. **Message delivery confirmation** - Webhook when user receives message
2. **Read receipts** - Track when user opens message
3. **Message retry** - Auto-retry failed messages (exponential backoff)

### Medium Priority
1. Bulk message sending (send to multiple users)
2. Message scheduling (send at future time)
3. Rich media messages (images, files, templates)

### Low Priority
1. Message editing (edit sent message)
2. Message deletion (retract message)
3. Broadcast lists (send to group efficiently)

## Rate Limit Configuration

**Current Defaults:**
```python
WAHA_MESSAGES_PER_HOUR = 10  # Max 10 messages per user per hour
WAHA_ANTI_BAN_ENABLED = true  # Enable anti-ban delays
WAHA_MESSAGE_DELAY_MS = 250   # Average delay (100-500 range)
```

**Recommendations by Use Case:**

| Scenario | Messages/Hour | Anti-Ban | Rationale |
|----------|---------------|----------|-----------|
| Clinic (auto responses) | 10 | Enabled | Conservative (safe) |
| High-volume clinic | 20 | Enabled | More aggressive |
| Promotional (risky) | 5 | Disabled | Super safe |
| Development/Testing | 100 | Disabled | No limits needed |
