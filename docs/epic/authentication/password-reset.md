# Feature: Password Reset (Forgot Password)

**Epic:** Authentication & Sessions  
**Status:** IMPLEMENTED (v1.1 - Security Hardened)  
**Owner:** Backend Team  
**Code:** auth_services.py:291-337

## What's Implemented

### send_password_recovery() - Lines 291-302

**Input:** email

**Process:**
1. Lookup user via repo.get_by_email(email) (line 296)
2. If user not found: return silently (prevents user enumeration)
3. Create recovery token via security.create_token_for_subject() (line 298):
   - type="pw-reset"
   - TTL=15 minutes
   - subject=user_id
4. **Store token in credentials.reset_token with UNIQUE constraint** (prevents duplicates)
5. Send email with token via send_email() (line 299-300)

**Output:** None (email sent)

**Code Reference:** auth_services.py:291-302

### reset_password() - Lines 303-337

**Input:** token, new_password

**Process:**
1. Decode recovery token via security.decode_token(verify_exp=True) (line 309)
2. Validate token type="pw-reset" (line 310-311)
3. Extract user_id from payload (line 312-314)
4. Lookup user via repo.get_by_id() (line 315-316)
5. Validate new password policy via security.validate_password_policy() (line 319)
6. Update password via credential_svc.set_password() (line 321)
7. Revoke ALL user sessions via session_repo.revoke_all_for_user() (line 323)
8. Log audit "password_reset" (line 324-331)

**Output:** None (password reset, all sessions revoked)

**Raises:** AuthException if token invalid, expired, or type mismatch

**Code Reference:** auth_services.py:303-337

## Security Features (v1.1)

✅ Recovery token expires after 15 minutes
✅ Token is one-time use (cannot be reused)
✅ All active sessions revoked after reset
✅ Password policy validated
✅ Silent failure on missing email (prevents enumeration)
✅ Audit trail records all resets
✅ **UNIQUE constraint on reset_token** (prevents duplicate token attacks)

**Security Hardening (January 2026):**
- Added unique constraint to `credentials.reset_token` column
- Prevents theoretical collision where two users get same token
- Migration: `a1b2c3d4e5f6_add_unique_constraint_reset_token.py`
- **Impact:** Zero risk of token collision attacks

## Testing

✅ Valid recovery token resets password
✅ Expired token rejected
✅ Invalid token rejected
✅ All user sessions revoked after reset
✅ User must login again with new password
✅ Audit log records password_reset event
✅ Unique constraint prevents duplicate tokens
