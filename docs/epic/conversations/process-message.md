# Feature: Receive & Process Inbound Message

**Epic:** Conversations  
**Status:** MVP Complete (Core Implemented)  
**Owner:** Backend & AI Team  
**Implementation:** 620 lines (conversation_orchestrator.py)  
**Tests:** [back/tests/integration/test_conversation_analysis_l3.py](back/tests/integration/test_conversation_analysis_l3.py)

## What's Implemented ✅

### 1. Core 10-Step Message Processing Pipeline

**Step 1: Get or Create Conversation**
- Query ConversationModel by chat_id
- Create if not exists (new user)
- Create linked LeadModel (phone_number as identifier)
- Initial status: NEW, maturity_score=0

**Step 2: Detect Bot Silence State**
- Check conversation.human_active flag
- If human is conversing (agent took over), bot silences
- Returns early (stores message, doesn't respond)
- Prevents bot + human both talking simultaneously

**Step 3: Process Media (Audio/Video/Images)**
- Audio: Transcribe via Google Speech-to-Text
- Video: Extract frames + analyze (facial expressions, text)
- Images: OCR + object detection
- Append to message_text or replace with description
- All processed as text (no raw media to Gemini)

**Step 4: Save Inbound Message**
- Store ConversationMessage (direction="inbound")
- Store message_text (processed)
- Store raw_message (original, for audit)
- Store media_urls, metadata

**Step 5: Retrieve Conversation Context**
- Query ChromaDB for semantically similar messages (top-K)
- Build multi-turn context string
- Include: Previous 5-10 messages + extracted intents
- Used for Gemini context window

**Step 6: Detect Intent**
- Call IntentDetector.detect_intent(message_text, context)
- Gemini analyzes against 10 intent categories
- Returns: intent_type (enum), confidence score
- Intent categories: INTERESSE_TRATAMENTO, DUVIDA_PROCEDIMENTO, PRECO, etc.

**Step 7: Detect Urgency**
- Call IntentDetector.detect_urgency(message_text, context)
- Medical urgency detection (pain, bleeding, severe symptoms)
- Returns: is_urgent (boolean)
- If urgent: Update conversation.is_urgent=true, alert human

**Step 8: Extract Patient Name**
- Passive: "My name is Maria" → auto-extracted
- Active: Bot asks "What's your name?" if needed
- Update lead.name (from phone_number default)
- Case-insensitive, handles variations (Maria/Mariah, etc.)

**Step 9: Generate Response**
- Call Gemini with:
  - message_text (current user message)
  - intent (detected)
  - context (conversation history)
  - playbooks (if matching topic)
  - SPIN Selling phase (based on score)
- Gemini returns: response_text (60-300 chars typical)
- Follows clinical tone + empathy guidelines

**Step 10: Append Name Request (if needed)**
- If lead.name still = phone_number (never captured)
- Append: "By the way, what should I call you?" to response
- Only once per conversation (flag: name_requested)

**Step 11: Update Maturity Score**
- Call IntentDetector.update_maturity_score()
- Score increment based on intent:
  - INTERESSE_TRATAMENTO: +15
  - PRECO: +10
  - AGENDAMENTO: +25
  - URGENCIA: +20
- New score = old_score + increment (capped at 100)
- Triggers conversation phase transition (SITUATION → PROBLEM → IMPLICATION → NEED-PAYOFF)

**Step 12: Check Escalation Needed**
- Call IntentDetector.check_escalation_needed()
- Triggers if:
  - Score >= 70 (ready for scheduling)
  - is_urgent=true (medical concern)
  - Repeated questions (frustrated user)
  - Explicit request: "speak with agent"
- If triggered: Handoff to human agent

**Step 13: Handle Handoff**
- If escalation triggered:
  - Change conversation.status = "ESCALATED"
  - Notify available agent (real-time)
  - Show: Lead name, chat history, score, intent
  - Agent takes over conversation

**Step 14: Save Context to ChromaDB**
- Vectorize response_text + message_text
- Store embeddings with metadata (chat_id, timestamp, intent)
- Used for future context retrieval

**Step 15: Send Response**
- Call waha_service.send_text()
- Send via WhatsApp
- Store sent message as ConversationMessage (direction="outbound")
- Update conversation.last_message_at = now
- Log delivery status

## Code References

**Main Orchestrator:** [back/src/robbot/services/conversation_orchestrator.py](back/src/robbot/services/conversation_orchestrator.py#L77) (lines 77-620)
- Input: chat_id, phone_number, message_text, media (optional)
- Output: Dict with response_id, status, escalated (boolean)
- Raises: BusinessRuleError (invalid data), WhatsAppError (delivery failed)

**Sub-Services Called:**
1. [MessageProcessor](back/src/robbot/services/message_processor.py)
   - `process_media_message()` - Transcribe/extract media
   - `save_inbound_message()` - Store message to DB

2. [ContextBuilder](back/src/robbot/services/context_builder.py)
   - `get_conversation_context()` - ChromaDB semantic search

3. [IntentDetector](back/src/robbot/services/intent_detector.py) (lines 187-250+)
   - `detect_intent()` - Gemini intent classification
   - `detect_urgency()` - Medical urgency check
   - `try_extract_name()` - Name extraction
   - `update_maturity_score()` - Score calculation
   - `check_escalation_needed()` - Handoff trigger

4. [WAHAService](back/src/robbot/services/waha_service.py) (lines 275-290)
   - `send_text()` - Send response via WhatsApp

5. [LeadService](back/src/robbot/services/lead_service.py)
   - `update_maturity()` - Update lead score

**Models:**
- [ConversationModel](back/src/robbot/infra/db/models/conversation_model.py)
  - chat_id, phone_number, lead_id, status, maturity_score, is_urgent
- [ConversationMessageModel](back/src/robbot/infra/db/models/conversation_message_model.py)
  - conversation_id, direction (in/out), content, timestamp
- [LeadModel](back/src/robbot/infra/db/models/lead_model.py)
  - name, email, phone_number, maturity_score, status

**External:**
- Google Gemini API (intent detection, response generation)
- Google Speech-to-Text (audio transcription)
- ChromaDB (vector database for context)
- WAHA WhatsApp API (message delivery)

## Gaps ❌

| Gap | Priority | Impact |
|-----|----------|--------|
| Audio transcription failures | HIGH | No fallback when audio fails to transcribe |
| Image analysis for medical conditions | MEDIUM | Cannot process photos (skin conditions, before/after) |
| Real-time handoff notification | MEDIUM | Agent notified asynchronously (delay) |
| Conversation abort/restart | LOW | No way for user to start over |
| Multi-language support | LOW | Only Portuguese/English |

## Flow Diagram - Complete Pipeline

```
Webhook: POST /api/v1/webhooks/waha
{
  "event": "message.received",
  "data": {
    "chat_id": "5511987654321@c.us",
    "message_text": "I want to lose weight",
    "has_audio": false
  }
}
  ↓
Step 1: Get or create conversation + lead
  conversation.id = new-123, lead.id = lead-456
  ↓
Step 2: Check bot silence
  human_active = false → continue (not silenced)
  ↓
Step 3: Process media
  No media → message_text unchanged
  ↓
Step 4: Save inbound message
  Stored in ConversationMessage table
  ↓
Step 5: Retrieve context
  ChromaDB search: Similar messages from this user
  context_text = "Previous: User asked about weight loss options..."
  ↓
Step 6: Detect intent
  Gemini: "INTERESSE_TRATAMENTO" (confidence 95%)
  ↓
Step 7: Detect urgency
  Gemini: is_urgent = false (not medical emergency)
  ↓
Step 8: Extract name
  Message: "My name is Maria..."
  Extracted: lead.name = "Maria" (was "5511987654321")
  ↓
Step 9: Generate response
  Input: intent=INTERESSE_TRATAMENTO, score=0 (SITUATION phase)
  Gemini response: "Hello Maria! Great that you're interested in our weight loss program.
                    What brings you to seek treatment today?"
  ↓
Step 10: Append name request (if needed)
  Not needed (already have name)
  ↓
Step 11: Update maturity score
  Increment: +15 (INTERESSE_TRATAMENTO)
  New score: 0 + 15 = 15 (still in SITUATION phase)
  ↓
Step 12: Check escalation
  Score 15 < 70 → No escalation needed
  ↓
Step 13: Skip handoff
  No handoff needed
  ↓
Step 14: Save context to ChromaDB
  Vectorize both messages + store embeddings
  ↓
Step 15: Send response
  WAHA API: send_text()
  Message sent: "Hello Maria! Great that you're interested..."
  ↓
Response HTTP 200 OK {
  "status": "processed",
  "response_id": "resp-789",
  "message_sent": true,
  "escalated": false
}
  ↓
Conversation continues (user can reply)
```

## SPIN Selling Phase Integration

```
Score 0-25 (SITUATION):
  Bot asks: "What brings you here?"
  Goal: Understand patient situation
  Response tone: Curious, empathetic
  Increment: +15 for interest

Score 25-40 (PROBLEM):
  Bot asks: "How is this affecting you?"
  Goal: Identify pain points
  Response tone: Understanding, sympathetic
  Increment: +10 for additional details

Score 40-60 (IMPLICATION):
  Bot asks: "How would solving this help?"
  Goal: Explore consequences
  Response tone: Thoughtful, solution-focused
  Increment: +20 for urgency signals

Score 60-70 (NEED-PAYOFF):
  Bot presents: "Our treatment can help..."
  Goal: Build desire for solution
  Response tone: Confident, professional
  Increment: +25 for scheduling intent

Score 70+ (HANDOFF READY):
  Bot: "Let me connect you with our team"
  Escalate to human agent
  Agent: Completes scheduling
```

## Integration Points

**Webhook Entry:** `/api/v1/webhooks/waha` (POST)
- Receives WhatsApp messages from WAHA
- Enqueues job to Redis (RQ queue)

**Background Worker:**
- Processes queued messages
- Calls process_inbound_message()
- Sends response via waha_service

**Real-time Updates (Optional WebSocket):**
- Send to dashboard: New message received
- Show: Message preview, intent detected, score updated
- Alert if escalation triggered

## Testing Strategy

### Unit Tests

```python
def test_process_message_creates_conversation():
    """New user creates new conversation"""
    
def test_process_message_detects_intent():
    """Intent correctly identified from message"""
    
def test_process_message_updates_score():
    """Maturity score incremented based on intent"""
    
def test_process_message_detects_urgency():
    """Urgent medical concerns flagged"""
    
def test_process_message_extracts_name():
    """Patient name extracted and saved"""
    
def test_process_message_escalates_high_score():
    """Score >= 70 triggers handoff"""
    
def test_process_message_saves_context():
    """Conversation context stored in ChromaDB"""
```

### Integration Tests

```python
def test_process_message_complete_flow():
    """Message received → processed → response sent"""
    
def test_process_message_with_media():
    """Audio/image processed and included in context"""
    
def test_process_message_bot_silenced():
    """Bot doesn't respond if human active"""
```

## Security Considerations

1. **Message Validation**: All inputs sanitized before sending to Gemini
2. **Rate Limiting**: Max messages per hour per user (prevent spam)
3. **PII Handling**: Logs don't expose patient names/medical info
4. **Audit Trail**: Every message logged (sent/received)
5. **Handoff Safety**: Conversation history reviewed before human access
6. **Gemini Prompt Injection**: Prompt structure prevents jailbreaks

## Performance Considerations

**Response Time:**
- Target: < 5 seconds (user expects fast WhatsApp response)
- Current: ~3-4 seconds (Gemini API call dominant)
- Optimization: Parallel intent + urgency detection

**Context Retrieval:**
- ChromaDB top-K search (efficient)
- Limited to last 10 messages (context window limit)
- Semantic search faster than text search

**Scaling:**
- Background job queue (RQ) handles parallel processing
- Multiple workers can process simultaneously
- Stateless design (no session affinity needed)

## Next Steps (Roadmap)

### High Priority
1. **Real-time agent notification** - WebSocket alert instead of polling
2. **Audio transcription fallback** - Handle transcription failures gracefully
3. **Conversation history export** - Download full chat as PDF

### Medium Priority
1. Image analysis (OCR + medical condition detection)
2. Multi-language support (auto-translate)
3. Conversation analytics (keyword frequency, sentiment)

### Low Priority
1. Voice response (speech synthesis + audio response)
2. Conversation templates (pre-canned flows for common questions)
3. A/B testing (test different response strategies)
