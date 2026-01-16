# Feature: Lead Maturity Score Progression

**Epic:** Leads Management  
**Status:** MVP Complete (Core Implemented)  
**Owner:** Backend & Analytics Team  
**Implementation:** 377 lines (lead_service.py)  
**Tests:** [back/tests/integration/test_conversion_reports_l2.py](back/tests/integration/test_conversion_reports_l2.py)

## What's Implemented ✅

### 1. Maturity Score Basics
- **update_maturity()**: Update lead score (0-100 range)
  - Validate input (must be 0-100)
  - Query LeadModel by lead_id
  - Update lead.maturity_score
  - Log change (old → new)
  - Return updated lead object

### 2. Score Validation
- Score must be integer 0-100
- Raises BusinessRuleError if out of range
- Prevents invalid states

### 3. Persistent Storage
- Score saved to PostgreSQL immediately
- No caching (always fresh)
- Audit log records old → new transition

### 4. Score Increments (Triggered by Intent Detection)

| Intent Type | Increment | Rationale |
|-------------|-----------|-----------|
| INTERESSE_TRATAMENTO | +15 | Initial interest expressed |
| DUVIDA_PROCEDIMENTO | +5 | Asking about procedures |
| PRECO_VALOR | +10 | Asking about pricing (buying signal) |
| LOCALIZACAO_HORARIO | +25 | Asking about location/hours (near booking) |
| URGENCIA_DOR | +20 | Urgent medical need (high intent) |
| RESULTADO_TEMPO | +5 | Asking about results (curious) |
| COMPARACAO_OPCOES | +5 | Comparing with competitors |
| AGENDAMENTO | +25 | Ready to book (highest intent) |
| RECLAMACAO_PROBLEMA | -10 | Complaint/issue (negative) |
| OUTRO | 0 | Generic conversation |

### 5. Score-Based Conversation Strategy

```
Score Range → SPIN Phase → Bot Behavior → Example Message
─────────────────────────────────────────────────────────────
0-25 (SITUATION):    Discover      Ask questions about patient situation
                                   "What brings you to seek treatment today?"

25-40 (PROBLEM):     Investigate  Ask about pain points and problems
                                   "How is this affecting your daily life?"

40-60 (IMPLICATION): Develop      Explore consequences of not solving
                                   "What would happen if this continues?"

60-70 (NEED-PAYOFF): Build desire Present benefits and solution
                                   "Our treatment can help you achieve..."

70-100 (READY):      Convert      Handoff to human for scheduling
                                   "Let me connect you with our team..."
```

### 6. Lead Status Progression

```
NEW → ENGAGED → INTERESTED → READY → SCHEDULED → CONVERTED/LOST

Score-based transitions:
- NEW (score 0-25): Initial contact
- ENGAGED (score 25-50): Active conversation
- INTERESTED (score 50-70): Strong interest shown
- READY (score 70+): Ready for human handoff
- SCHEDULED: Appointment booked
- CONVERTED: Appointment attended
- LOST: No further response (> 7 days)
```

### 7. Real-Time Analytics

- Score update triggers immediate dashboard refresh
- Real-time conversion funnel updated
- Heatmap of score distribution
- Conversion rate recalculated

## Code References

**Main Method:** [back/src/robbot/services/lead_service.py](back/src/robbot/services/lead_service.py#L68-L105) (lines 68-105)
- Input: lead_id (string), new_score (integer 0-100)
- Output: LeadModel (updated lead object)
- Raises: NotFoundException (lead not found), BusinessRuleError (invalid score)

**Score Update Trigger:** [back/src/robbot/services/intent_detector.py](back/src/robbot/services/intent_detector.py#L187-L250) (lines 187-250)
- `update_maturity_score()` - Calculates increment based on intent
- Called after each message from user

**Repository:**
- [back/src/robbot/adapters/repositories/lead_repository.py](back/src/robbot/adapters/repositories/lead_repository.py)
  - `get_by_id()` - Fetch lead by ID
  - `update()` - Persist changes

**Models:**
- [LeadModel](back/src/robbot/infra/db/models/lead_model.py)
  - id, phone_number, name, email
  - maturity_score (0-100)
  - status (enum: NEW, ENGAGED, INTERESTED, READY, SCHEDULED, CONVERTED, LOST)
  - created_at, updated_at

**Analytics:**
- [back/src/robbot/services/analytics_service.py](back/src/robbot/services/analytics_service.py)
  - Score distribution histogram
  - Conversion funnel (by score bucket)
  - Lost lead analysis (stuck at score X for > 7 days)

## Gaps ❌

| Gap | Priority | Impact |
|-----|----------|--------|
| Score decay over time | MEDIUM | Old leads don't reduce score (stale) |
| Custom score increments per clinic | MEDIUM | All clinics use same formula |
| Negative score floor | LOW | Score can't go below 0 (edge case) |
| Score milestone notifications | LOW | No alert when lead reaches 70 |

## Validation Rules

```
Score Range:
  - Minimum: 0 (brand new lead)
  - Maximum: 100 (absolutely ready to book)
  - Type: Integer (no decimals)

Valid Transitions:
  - Increase only (monotonic)
  - Decrease by -10 only (for complaints)
  - Never jump > 25 per message
  - Example: 15 + 15 = 30, not 15 + 50 = 65

Score → Status Mapping:
  - 0-25: NEW / ENGAGED
  - 25-50: ENGAGED / INTERESTED
  - 50-70: INTERESTED / READY (borderline)
  - 70+: READY (escalate)
```

## Flow Diagram - Score Update

```
User sends message: "I want to schedule an appointment"
  ↓
Intent Detector runs:
  Detects intent = AGENDAMENTO (scheduling request)
  ↓
intent_detector.update_maturity_score():
  Get current score: 45
  Intent increment: +25 (AGENDAMENTO)
  New score: 45 + 25 = 70
  ↓
lead_service.update_maturity():
  Validate: 70 is between 0-100 ✓
  Query lead: found
  Update: lead.maturity_score = 70
  Save to PostgreSQL
  ↓
Log event: "Score updated (lead_id=L123, 45 → 70)"
  ↓
Check escalation:
  Is score >= 70? YES
  Trigger handoff to human agent
  ↓
Update conversation status: "ESCALATED"
  ↓
Real-time dashboard updates:
  - Lead moved to "READY" column
  - Conversion funnel refreshed
  - Agent notification sent
```

## Flow Diagram - Score Distribution Over Time

```
Day 1: New lead receives first message
  Score: 0 → 15 (INTERESSE_TRATAMENTO)
  Status: NEW → ENGAGED

Day 1 (later): User asks about treatment
  Score: 15 → 20 (DUVIDA_PROCEDIMENTO)
  Status: ENGAGED

Day 2: User asks about pricing
  Score: 20 → 30 (PRECO_VALOR)
  Status: ENGAGED → INTERESTED

Day 3: User asks about location/hours
  Score: 30 → 55 (LOCALIZACAO_HORARIO)
  Status: INTERESTED

Day 3 (later): User sends urgent medical question
  Score: 55 → 75 (URGENCIA_DOR)
  Status: INTERESTED → READY
  ↓
Escalate to human agent
```

## Integration Points

**Called by:** [IntentDetector.update_maturity_score()](back/src/robbot/services/intent_detector.py)
- Runs after every user message
- Part of process_inbound_message() pipeline

**Calls:**
- LeadRepository (get_by_id, update)
- AuditService (log score change)

**Triggers:**
- Dashboard real-time update (WebSocket)
- Escalation logic (if score >= 70)
- Analytics recalculation

## Testing Strategy

### Unit Tests

```python
def test_update_maturity_increases_score():
    """Score updated from 10 to 25"""
    
def test_update_maturity_validates_range():
    """Score > 100 raises BusinessRuleError"""
    
def test_update_maturity_negative_score_invalid():
    """Score < 0 raises BusinessRuleError"""
    
def test_update_maturity_persists_to_db():
    """Change saved to PostgreSQL"""
    
def test_update_maturity_logs_change():
    """Audit log records old → new"""
    
def test_update_maturity_not_found():
    """Non-existent lead raises NotFoundException"""
```

### Integration Tests

```python
def test_score_progression_complete_flow():
    """Score: 0 → 15 → 30 → 55 → 75 (escalate)"""
    
def test_score_update_triggers_escalation():
    """Score 70+ triggers handoff"""
    
def test_analytics_updated_on_score_change():
    """Dashboard metrics recalculated"""
```

## Security Considerations

1. **Input Validation**: Score range enforced (0-100)
2. **Audit Trail**: All score changes logged with timestamp
3. **Authorization**: Only bot can update scores (no user/agent modification)
4. **Immutable History**: Old scores not deleted (audit trail)

## Analytics Integration

**Conversion Funnel:**
```
100 new leads
  ↓ (40% reach score 25)
40 engaged
  ↓ (75% reach score 50)
30 interested
  ↓ (60% reach score 70)
18 ready for handoff
  ↓ (85% complete booking)
15 converted
  ↓
Conversion rate: 15%
```

**Dropout Analysis:**
- Where do leads lose interest?
- Which intents predict highest conversion?
- Which clinics have best conversion rates?

## Next Steps (Roadmap)

### High Priority
1. **Score decay** - Reduce score if no activity for 7+ days
2. **Custom score formula** - Allow clinics to adjust increments
3. **Score breakdown dashboard** - Show which intents drive conversion

### Medium Priority
1. Predictive scoring (ML model for conversion probability)
2. Score milestone alerts ("Lead almost ready!" at score 60)
3. Competitor analysis (score comparison with other clinics)

### Low Priority
1. A/B testing (test different score formulas)
2. Score-based auto-responses (customize bot behavior per score)
3. Score history graph (show score progression over time)
