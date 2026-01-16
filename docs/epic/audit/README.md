# Epic: Audit (Registro de Auditoria)

**Status:** IMPLEMENTADO  
**Implementation:** 110 linhas (audit_service.py)
**Owner:** Backend & Compliance Team
**Last Updated:** Janeiro 2026

---

## O Que Existe (Código Real)

### 1. log_action()
**Entrada:** action, entity_type, entity_id, user_id?, old_value?, new_value?, ip_address?  
**Saída:** AuditLogModel criado

- action: string (CREATE, UPDATE, DELETE, login_success, etc.)
- entity_type: string (Lead, User, Conversation, etc.)
- entity_id: string (identificador da entidade)
- user_id: int | None (quem fez a ação)
- old_value: dict | None (serializado para JSON)
- new_value: dict | None (serializado para JSON)
- ip_address: string | None

**Processo:**
1. Serializa old_value e new_value para JSON (json.dumps)
2. Cria AuditLogModel com todos os campos
3. Persiste via AuditLogRepository.create()
4. Flush session
5. Log: [SUCCESS]

**Arquivo:** audit_service.py:19-52

---

### 2. log_create()
**Entrada:** entity_type, entity_id, user_id?, entity_data?, ip_address?  
**Saída:** AuditLogModel

- Wrapper sobre log_action com action="CREATE"
- Passa entity_data como new_value
- **Arquivo:** audit_service.py:54-66

---

### 3. log_update()
**Entrada:** entity_type, entity_id, user_id?, old_data?, new_data?, ip_address?  
**Saída:** AuditLogModel

- Wrapper sobre log_action com action="UPDATE"
- Passa old_data como old_value
- Passa new_data como new_value
- **Arquivo:** audit_service.py:68-82

---

### 4. log_delete()
**Entrada:** entity_type, entity_id, user_id?, entity_data?, ip_address?  
**Saída:** AuditLogModel

- Wrapper sobre log_action com action="DELETE"
- Passa entity_data como old_value
- **Arquivo:** audit_service.py:84-97

---

### 5. get_user_logs()
**Entrada:** user_id, limit=100  
**Saída:** list[AuditLogModel]

- Query AuditLogRepository.get_by_user(user_id, limit)
- Retorna últimos N registros do usuário
- **Arquivo:** audit_service.py:99-102

---

### 6. get_entity_logs()
**Entrada:** entity_type, entity_id, limit=100  
**Saída:** list[AuditLogModel]

- Query AuditLogRepository.get_by_entity(entity_type, entity_id, limit)
- Retorna histórico completo da entidade
- **Arquivo:** audit_service.py:104-110

---

### 7. get_recent_logs()
**Entrada:** limit=100  
**Saída:** list[AuditLogModel]

- Query AuditLogRepository.get_recent(limit)
- Retorna logs mais recentes do sistema inteiro
- **Arquivo:** audit_service.py:112-115

---

## AuditLogModel

**Campos:**
- id: UUID (primary key)
- user_id: int | None (FK para User)
- action: str (CREATE, UPDATE, DELETE, login_success, etc.)
- entity_type: str (Lead, User, Conversation, etc.)
- entity_id: str (ID da entidade)
- old_value: str | None (JSON serializado)
- new_value: str | None (JSON serializado)
- ip_address: str | None
- created_at: datetime (immutable, auto-set)

**Imutável:** Nenhum update/delete após criação

---

## Integração com Outros Serviços

- **AuthService:** log_action para login_success, login_failure, login_success_mfa_pending, mfa_login_success, mfa_verification_failed, token_refresh, logout, password_reset, password_change
- **UserService:** log_action para user_block, user_unblock
- **HandoffService:** NÃO integrado (sem audit)
- **ConversationOrchestrator:** Possivelmente integrado (não verificado)

---

## Gaps / Limitações

1. **Sem filtro de PII** - old_value/new_value podem conter senhas/tokens se não sanitizado by caller
2. **Sem rotação de logs** - Nenhuma cleanup policy para logs antigos
3. **Sem rate limiting** - Sem proteção contra audit spam
4. **Sem context propagation** - Cada chamada de log_action é independente, sem transaction context
5. **JSON simples** - Sem compressão ou criptografia de payloads


| Gap | Priority |
|-----|----------|
| Audit log export (CSV/Excel) | MEDIUM |
| Audit log visualization | MEDIUM |
| Automated retention policy | MEDIUM |
| PII masking in logs | LOW |
| Log encryption at rest | LOW |
| Real-time audit dashboard | LOW |

## Flows

### Login Audit
authenticate_user() successful
  → audit_svc.log_action("login_success", resource_id=user_id)
  → Store: user_id, action, IP, User-Agent, timestamp

### Conversation Message Audit
process_inbound_message() processes message
  → audit_svc.log_action("conversation_message", resource_id=conversation_id)
  → Store: user_id (bot user), action, message direction, timestamp

### Lead Status Change
update_maturity_score(lead_id, new_score)
  → audit_svc.log_update("lead", lead_id, old_score, new_score)
  → Store: old value, new value, timestamp

### Query Audit Trail
Compliance check for user/lead/conversation
  → get_entity_logs(resource_type="lead", resource_id=lead_id)
  → Retrieve all modifications with timestamps

## Testing

**Unit Tests (TBD):**
- `test_log_action_success`: Verify action logged correctly
- `test_log_create_captures_new_values`: Verify new resource data captured
- `test_log_update_shows_old_new`: Verify before/after values
- `test_get_user_logs_paginated`: Verify pagination works
- `test_get_entity_logs_filtered`: Verify filtering by resource

## Next Steps

1. Add audit log export (CSV, Excel, JSON)
2. Build audit log visualization dashboard (timeline view)
3. Implement automated retention policy (delete logs > 1 year)
4. Add PII masking (don't log passwords, sensitive data)
5. Real-time audit dashboard (WebSocket feed)
