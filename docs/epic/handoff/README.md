# Epic: Handoff (Bot → Human)

**Status:** IMPLEMENTADO  
**Implementation:** 231 linhas (handoff_service.py)
**Owner:** Backend Team
**Last Updated:** Janeiro 2026

---

## O Que Existe (Código Real)

### 1. trigger_handoff()
**Entrada:** conversation_id, reason, score, additional_context  
**Saída:** {"status", "conversation_id", "reason", "message"}

- Valida se conversa em estado válido (rejeita COMPLETED/CLOSED)
- Seta ConversationStatus.PENDING_HANDOFF
- Armazena escalation_reason + updated_at
- Gera mensagem de transição (_generate_transition_message)
- Log: [SUCCESS]

**Razões suportadas:**
- score_high: "Vejo que você está bem interessado (score: X)! 🎯\nVou conectar você com um especialista..."
- bot_confused: "Entendo que você precisa de orientação mais específica..."
- manual: "Um de nossos atendentes vai assumir essa conversa agora..."

**Arquivo:** handoff_service.py:41-77

---

### 2. assign_to_human()
**Entrada:** conversation_id, user_id (UUID atendente)  
**Saída:** ConversationModel atualizado

- Valida estado (PENDING_HANDOFF, ACTIVE_BOT, ESCALATED permitidos)
- Seta ConversationStatus.ACTIVE_HUMAN
- Seta assigned_to = user_id + assigned_at = datetime.now(UTC)
- Log: [SUCCESS]

**Arquivo:** handoff_service.py:79-111

---

### 3. mark_as_completed()
**Entrada:** conversation_id, user_id  
**Saída:** {"status": "completed", "conversation_id", "metrics": {...}}

- Valida ConversationStatus.ACTIVE_HUMAN exato
- Valida assigned_to == user_id (usuário que atribuiu pode completar)
- Seta ConversationStatus.COMPLETED + completed_at = datetime.now(UTC)
- Se conversation.lead existe: seta lead.status = LeadStatus.SCHEDULED + maturity_score = 100
- Calcula métricas via _calculate_metrics():
  * **total_conversation_time_minutes** (created_at → completed_at)
  * **time_to_handoff_minutes** (created_at → assigned_at)
  * **human_interaction_time_minutes** (assigned_at → completed_at)
  * **final_score** (lead.maturity_score)
  * **escalation_reason** (conversation.escalation_reason)
- Log: [SUCCESS] com métricas

**Arquivo:** handoff_service.py:113-165

---

### 4. return_to_bot()
**Entrada:** conversation_id, user_id  
**Saída:** ConversationModel atualizado

- Valida ConversationStatus.ACTIVE_HUMAN
- Valida assigned_to == user_id
- Seta ConversationStatus.ACTIVE_BOT
- Limpa assigned_to, assigned_at, escalation_reason
- Log: [SUCCESS]

**Arquivo:** handoff_service.py:200-231

---

## Estados de Conversa (ConversationStatus Enum)

```
ACTIVE_BOT (padrão após criação)
   ↓ trigger_handoff()
PENDING_HANDOFF
   ↓ assign_to_human()
ACTIVE_HUMAN
   ├→ mark_as_completed()
   │   → COMPLETED [final, lead.status = SCHEDULED]
   └→ return_to_bot()
       → ACTIVE_BOT [continua com bot]
```

---

## Gaps / Limitações

1. **Mensagens com emojis** - Usar em production é compliance risk; 🎯, 👤 devem ser removidos
2. **Sem notificação real-time** - trigger_handoff não notifica atendente (sem webhook/WebSocket)
3. **Sem SLA timeout** - Não há timer para reatribuir handoff não respeitado
4. **Sem agent availability check** - assign_to_human não valida se agente está disponível
5. **Sem reason validation** - trigger_handoff aceita qualquer reason string, sem enum

---

## Integração com Outros Epics

- **Leads:** mark_as_completed atualiza lead.status = SCHEDULED
- **Conversations:** Status transitions controlled
- **Audit:** Nenhuma integração de auditoria (não chama audit_service)
- **Analytics:** Métricas calculadas mas não persistidas (via metrics_service)

## Code References

**Main Service:** [back/src/robbot/services/handoff_service.py](back/src/robbot/services/handoff_service.py) (358 lines)
- `trigger_handoff()` - Lines 34-88: Initiate handoff
- `assign_to_human()` - Lines 89-133: Agent assignment
- `mark_as_completed()` - Lines 134-195: Close conversation
- `_calculate_metrics()` - Lines 223-257: Metric aggregation
- `_generate_transition_message()` - Lines 196-222: Message generation
- `return_to_bot()` - Lines 258-298: Resume automation

**Dependencies:**
- NotificationService: Send agent notifications
- ConversationRepository: Update conversation status
- LeadRepository: Update lead assignment

## Gaps ❌

| Gap | Priority |
|-----|----------|
| Agent availability queue | MEDIUM |
| Handoff SLA tracking | MEDIUM |
| Agent skill routing | MEDIUM |
| Conversation queue priority | LOW |
| Handoff analytics | LOW |

## Flows

### Trigger Handoff (Score >= 70)
Score Update → check_escalation_needed() → trigger_handoff()
  → Calculate metrics
  → Generate transition message
  → Notify available agents
  → Update conversation.status = TRANSFERRED

### Agent Assignment
Handoff Triggered → Find available agents → Load balance → assign_to_human()
  → Send notification with context
  → Provide full conversation history
  → Update assignment tracking

### Completion
Agent resolves issue → mark_as_completed()
  → Close conversation
  → Update lead status to CONVERTED/LOST
  → Calculate agent metrics
  → Record in analytics

## Next Steps

1. Implement agent availability queue (priority-based)
2. Add handoff SLA tracking (target response time)
3. Skill-based routing (aesthetics expert, diet specialist, etc)
4. Conversation priority queue (urgent → high priority)
5. Handoff analytics dashboard (handoff reasons, resolution rates)
