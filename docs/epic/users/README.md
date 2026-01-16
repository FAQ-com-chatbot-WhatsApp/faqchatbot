# Epic: Users (Gestão de Usuários)

**Status:** IMPLEMENTADO  
**Implementation:** 94 linhas (user_service.py)
**Owner:** Backend Team
**Last Updated:** Janeiro 2026

---

## O Que Existe (Código Real)

### 1. list_users()
**Entrada:** skip=0, limit=100  
**Saída:** (list[UserOut], int)

- Query com skip/limit via UserRepository
- Count total via db.query(UserModel).count()
- Converte para UserOut schema
- **Arquivo:** user_service.py:21-29

---

### 2. get_user()
**Entrada:** user_id  
**Saída:** UserOut

- Fetch via UserRepository.get_by_id()
- Lança NotFoundException se não existe
- **Arquivo:** user_service.py:31-37

---

### 3. update_user()
**Entrada:** user_id, payload: UserUpdate {full_name?, is_active?}  
**Saída:** UserOut

- Fetch user
- Atualiza full_name se provided
- Atualiza is_active se provided
- Persiste via UserRepository.update_user()
- **Arquivo:** user_service.py:39-51

---

### 4. deactivate_user()
**Entrada:** user_id  
**Saída:** None

- Soft delete: seta is_active=False
- NÃO revoga sessões (apenas update)
- **Arquivo:** user_service.py:53-59

---

### 5. block_user()
**Entrada:** user_id, reason: str | None  
**Saída:** UserOut

- Seta is_active=False (se ativo)
- **Revoga TODAS as sessões** via AuthSessionRepository.revoke_all_for_user(user_id, reason)
- **Audit:** log_action("user_block", old={"is_active": True}, new={"is_active": False, "reason": reason})
- Erro de audit é suppressado
- **Arquivo:** user_service.py:61-77

---

### 6. unblock_user()
**Entrada:** user_id, reason: str | None  
**Saída:** UserOut

- Seta is_active=True (se inativo)
- **Audit:** log_action("user_unblock", old={"is_active": False}, new={"is_active": True, "reason": reason})
- Erro de audit suppressado
- **Arquivo:** user_service.py:79-94

---

## Dependências

- **UserRepository:** get_by_id(), list_users(), update_user()
- **AuthSessionRepository:** revoke_all_for_user(user_id, reason)
- **AuditService:** log_action(action, entity_type, entity_id, user_id, old_value, new_value)
- **UserOut Schema:** Conversão segura sem passwords/tokens

---

## Gaps / Limitações

1. **Sem revoke_all em deactivate** - deactivate_user() NOT revoga sessões (apenas soft delete)
2. **Sem role management** - update_user não permite mudar role
3. **Sem credential cleanup** - Block não limpa password/MFA setup
4. **Sem hard delete** - Apenas is_active flag
5. **Sem email verification reset** - Block não limpa email_verified
- Code verification (TOTP + backup code support)

### 4. Data Models
- User: id (int), email (unique), full_name, is_active, role (ADMIN/AGENT/VIEWER)
- Credential: 1:1 with User, password_hash (bcrypt), mfa_enabled, mfa_secret, backup_codes_hash
- AuthSession: session_id (UUID), jti (refresh token ID), ip_address, user_agent, device_name, expires_at
- RevokedToken: track revoked refresh tokens

### 5. Access Control
- Role enum: ADMIN (full access), AGENT (view assigned conversations), VIEWER (read-only)
- Session-based authentication with JWT tokens
- Device tracking for security

## Code References

**User Service:** [back/src/robbot/services/user_service.py](back/src/robbot/services/user_service.py) (216 lines)
- `list_users()` - Lines 23-31
- `get_user()` - Lines 32-38
- `update_user()` - Lines 39-52
- `deactivate_user()` - Lines 53-60
- `block_user()` / `unblock_user()` - Lines 61-105

**Auth Service:** [back/src/robbot/services/auth_services.py](back/src/robbot/services/auth_services.py) (462 lines)
- `signup()` - Password validation, user creation, email token
- `authenticate_user()` - Email verified check, MFA detection, session creation
- `verify_mfa_and_complete_login()` - TOTP verification, final tokens
- `refresh()` - Token refresh with JTI validation
- `logout()` / `logout_session()` - Session revocation

**MFA Service:** [back/src/robbot/services/mfa_service.py](back/src/robbot/services/mfa_service.py) (105 lines)
- `setup_mfa()` - Generate secret, QR, backup codes
- `verify_totp()` - TOTP validation with time window
- `verify_backup_code()` - One-time backup code validation

## Gaps ❌

| Gap | Priority |
|-----|----------|
| Password reset/recovery | HIGH |
| Email verification resend | HIGH |
| Session timeout enforcement | MEDIUM |
| Device list/revoke endpoints | MEDIUM |
| Role-based API enforcement | MEDIUM |
| Rate limiting on auth endpoints | MEDIUM |
| Account deactivation cleanup | LOW |

## Flows

### User Signup
User enters email/password → signup()
  → Validate password (min length, complexity)
  → Hash with bcrypt (10+ rounds)
  → Create UserModel + CredentialModel
  → Generate email verification token (JWT)
  → Send verification email
  → Return success message

### Email Verification
User clicks link with token → verify_email()
  → Validate JWT token (decode)
  → Mark User.email_verified = true
  → Allow login

### Login with MFA
Email + Password → authenticate_user()
  → Check email_verified = true (BLOCKS if false)
  → Verify password via bcrypt
  → If MFA enabled: Generate temp token (5min TTL, type="mfa-pending")
  → If MFA disabled: Generate final tokens (access + refresh)
  → Create AuthSession with JTI
  → Return tokens

### MFA Completion
Temp Token + TOTP Code → verify_mfa_and_complete_login()
  → Validate temp token
  → Check TOTP code (pyotp, ±30s window)
  → If backup code: Validate one-time use
  → Generate final tokens
  → Return access + refresh tokens

### Token Refresh
Refresh Token → refresh()
  → Validate JWT signature
  → Lookup session via JTI
  → Check not revoked
  → Generate new access token
  → Optionally rotate refresh token (new JTI)
  → Return new tokens

## Testing

**Integration Tests:**
- [back/tests/integration/test_mfa_login_flow.py](back/tests/integration/test_mfa_login_flow.py) (284 lines)
  - Signup flow
  - Email verification requirement
  - MFA detection
  - Temporary token handling
  - TOTP verification
  - Token refresh

## Next Steps

1. Implement password reset/recovery flow (email token)
2. Add email verification resend endpoint
3. Enforce session timeout (cleanup old sessions)
4. Add device list/revoke endpoints (security)
5. Implement rate limiting on auth endpoints (2FA brute force protection)
6. Role-based endpoint authorization (decorator pattern)
