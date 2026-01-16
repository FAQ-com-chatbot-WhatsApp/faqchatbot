# Epic: Authentication & Sessions

**Status:** Production Ready (All Core Features + Security Hardening)  
**Version:** 1.1  
**Owner:** Backend Team  
**Last Updated:** January 15, 2026

## Overview

### Problem Statement

The platform requires secure user authentication with support for email verification and multi-factor authentication (TOTP). Users must be able to register, log in, manage sessions, and securely reset passwords using industry-standard practices.

### What's Implemented

- ✅ User registration (signup) with email verification tokens
- ✅ Login with email/password (authenticate_user)
- ✅ MFA detection: login flow branches based on MFA enablement
- ✅ Temporary tokens for MFA-pending state (5min TTL)
- ✅ TOTP verification and backup code support
- ✅ Token refresh with JTI rotation
- ✅ Idle timeout: Sessions inactive >30 days are revoked (v1.1)
- ✅ Logout with token revocation
- ✅ Session management (create, track via JTI)
- ✅ Email verification flow
- ✅ Audit logging for auth events
- ✅ Credential model (separated from User per ADR-001)
- ✅ Session cleanup job (automated expired session deletion)
- ✅ Device fingerprinting (parse device names from User-Agent)

### Scope

**In Scope (Implemented):**
- User registration/signup
- Email verification workflow
- Login with email/password
- MFA setup, verification (TOTP + backup codes)
- Password recovery/reset flow
- Password change (authenticated)
- Token refresh and JTI rotation
- Logout and session cleanup
- Session management (list, revoke specific/all sessions)
- Rate limiting (5 per 15min per IP on login)
- Audit logging (login_success, login_failure, MFA events, password changes)

**Out of Scope (Not Implemented):**
- OAuth/SAML third-party auth
- Biometric authentication
- SMS-based 2FA (only TOTP + backup codes supported)

---

## Architecture & Actual Flows (What's Implemented)

### 1. User Signup Flow

**Services:** `AuthService.signup()` + `EmailVerificationService`  
**Input:** email, password, full_name

**Process:**
1. Validate password policy (min 8 chars, at least 1 special char)
2. Check if email already exists
3. Hash password with bcrypt (10+ rounds)
4. Create UserModel with role=USER, is_active=true
5. Create CredentialModel (linked to User via 1:1 relationship)
6. Generate email verification token (JWT-based, 24h TTL by default)
7. Send verification email with token link
8. Return UserOut (no password exposed)

**Output:** User created, verification email sent

**Safeguards:** Duplicate email check, password policy validation, bcrypt hashing

**Code Reference:**
```python
# AuthService.signup() - 462 lines
# EmailVerificationService.generate_verification_token()
# CredentialService.set_password()
```

### 2. Email Verification Flow

**Services:** `EmailVerificationService`  
**Input:** verification_token from email link

**Process:**
1. Decode JWT token (verify signature, check expiration)
2. Extract user_id from token claims
3. Mark email_verified=true in CredentialModel
4. Clear email_verification_token
5. Return success

**Output:** Email marked verified, user can now login

**Safeguards:** Token signature validation, TTL check

**Code Reference:**
```python
# EmailVerificationService.verify_email()
# CredentialRepository.mark_email_verified()
```

### 3. Login (Authenticate User) Flow

**Services:** `AuthService.authenticate_user()` + MFA detection  
**Input:** email, password, [user_agent], [ip_address]

**Process:**
1. Lookup user by email
2. Check user is active
3. Check email is verified (blocks unverified accounts)
4. Verify password via bcrypt (constant-time comparison)
5. Check MFA enablement in CredentialModel
6. **Branch A (MFA Disabled):**
   - Create access + refresh tokens (JWT)
   - Extract JTI from refresh token
   - Create AuthSessionModel (tracks: JTI, IP, User-Agent, device_name)
   - Log audit: "login_success"
   - Return Token(access_token, refresh_token, mfa_required=False)
7. **Branch B (MFA Enabled):**
   - Create temporary tokens (5min TTL, type="mfa-pending")
   - Skip session creation (will be created after MFA verification)
   - Log audit: "login_success_mfa_pending"
   - Return Token(access_token=temporary_token, refresh_token="", mfa_required=True)

**Output:** Full tokens (normal) OR temporary token (MFA)

**Safeguards:** Email verification check, bcrypt comparison, audit logging

**Code Reference:**
```python
# AuthService.authenticate_user() - 100+ lines
# CredentialService.verify_password()
# security.create_access_refresh_tokens()
# security.create_token_for_subject(minutes=5, token_type="mfa-pending")
```

### 4. MFA Setup Flow

**Services:** `MfaService.setup_mfa()`  
**Input:** authenticated user_id

**Process:**
1. Generate TOTP secret (base32, pyotp library)
2. Create OTPAuth URI for QR code
3. Generate 10 backup codes (hex strings)
4. Hash backup codes with bcrypt
5. Store secret + hashed codes in CredentialModel (but NOT enable MFA yet)
6. Encode OTPAuth URI as base64 (placeholder for QR image)
7. Return secret, QR base64, backup codes (plain text)

**Note:** MFA not enabled until `verify_mfa()` confirms correct TOTP code

**Output:** QR code (base64), backup codes (plain text, shown once)

**Safeguards:** Codes hashed before storage, secret marked as unverified

**Code Reference:**
```python
# MfaService.setup_mfa()
# MfaService._generate_secret()
# MfaService._generate_backup_codes()
# CredentialRepository.enable_mfa()
```

### 5. MFA Verification Flow (Complete Login)

**Services:** `AuthService.verify_mfa_and_complete_login()` + `MfaService.verify_mfa()`  
**Input:** temporary_token (from step 3 Branch B), totp_code OR backup_code

**Process:**
1. Validate temporary token (check type="mfa-pending", not expired)
2. Extract user_id from temporary token
3. Call MfaService to verify:
   - **TOTP branch:** Compare code against TOTP window (±30s, pyotp.TOTP.verify)
   - **Backup code branch:** Constant-time comparison of hashed codes
   - Mark backup code as used (one-time) if used
4. If verification succeeds:
   - Create access + refresh tokens (final, 15min + 7d TTL)
   - Create AuthSessionModel with JTI from refresh token
   - Log audit: "mfa_login_success"
   - Return Token(access_token, refresh_token, mfa_required=False)
5. If verification fails:
   - Return 401 Unauthorized

**Output:** Full tokens on success

**Safeguards:** Temporary token validation, TOTP window check, one-time backup codes

**Code Reference:**
```python
# AuthService.verify_mfa_and_complete_login()
# MfaService.verify_mfa()
# MfaService.verify_backup_code()
```

### 6. Token Refresh Flow

**Services:** `AuthService.refresh()`  
**Input:** refresh_token (from HttpOnly cookie or request body)

**Process:**
1. Decode refresh token (validate signature, check TTL)
2. Extract JTI from token claims
3. Lookup AuthSessionModel by JTI
4. Verify session is active (not revoked)
5. Create new access token (15min TTL)
6. Optionally: rotate refresh token (new JTI, revoke old)
7. Update session's last_used timestamp
8. Log audit: "token_refresh"
9. Return new Token(access_token, [new_refresh_token])

**Output:** New access token (always), optionally new refresh token

**Safeguards:** JTI validation, session status check, audit logging

**Code Reference:**
```python
# AuthService.refresh()
# security.decode_token(verify_exp=True)
# AuthSessionRepository methods
```

### 7. Logout Flow

**Services:** `AuthService.logout()`  
**Input:** refresh_token or JTI

**Process:**
1. Extract JTI from refresh token
2. Mark AuthSessionModel as revoked (revoked=true)
3. Clear any caches/tokens if needed
4. Log audit: "logout"
5. Return success

**Output:** Session revoked, user logged out

**Safeguards:** Session ownership validation, audit trail

**Code Reference:**
```python
# AuthService.logout()
# AuthSessionRepository.mark_revoked()
```

---

## Functional Requirements (As Implemented)

### User Registration
- Email, password, full_name input with validation
- Duplicate email check
- Password policy enforcement
- Email verification token generation (JWT, 24h TTL)
- Verification email delivery (via email service)

### Email Verification
- Verification link with JWT token
- One-time token consumption
- Blocks login until verified

### Login (Authentication)
- Email/password input
- Email verification check (must verify first)
- MFA detection: different response if MFA enabled
- Session creation (IP, User-Agent, device_name tracking)
- Audit logging for success/failure

### Multi-Factor Authentication (TOTP + Backup)
- Setup: generate secret, QR code, 10 backup codes
- Verify: TOTP (pyotp, ±30s window) or backup code
- One-time backup code consumption
- MFA enable/disable per user

### Token Management
- Access tokens: 15min TTL (short-lived)
- Refresh tokens: 7d TTL (long-lived)
- JTI (JWT ID) tracking for session binding
- Token rotation optional on refresh
- HttpOnly cookies support (configurable)

### Session Management
- Session creation on login
- JTI-based session tracking
- Device info storage (IP, User-Agent, device_name)
- Session revocation on logout
- Last-used timestamp tracking

### Audit Logging
- Event types: login_success, login_failure, login_success_mfa_pending, mfa_login_success, mfa_verification_failed, token_refresh, logout, password_reset, password_change
- Metadata: user_id, timestamp, IP address, user_agent (optional)
- Failure reasons logged

### Password Management
- Password reset flow: send recovery email + reset with token
- Password change: verify current password + update + revoke all sessions
- Both operations audit logged and revoke all active sessions
- Code reference: auth_services.py:270-308 (reset), 341-369 (change)

---

## Non-Functional Requirements

### Security
- Password hashing: bcrypt with min 10 rounds
- JWT: HMAC-SHA256
- Cookies: HttpOnly, Secure, SameSite=Strict (configured)
- Constant-time password/token comparison (bcrypt, TOTP)
- No passwords in logs

### Performance
- Authentication latency: <500ms (depends on bcrypt rounds)
- Token validation: <50ms
- Session lookup: <10ms

### Compliance
- LGPD: audit trail of all auth events
- MFA: optional but supported
- Session isolation: per JTI

---

## Known Gaps & Limitations

### Password Reset IMPLEMENTED
**Status:** FULLY IMPLEMENTED 
**Implementation:**
- send_password_recovery(email): generates JWT type="pw-reset" (15min TTL) + sends email
- reset_password(token, new_password): validates token + resets + revokes ALL sessions
**Code Reference:** auth_services.py:270-308

### Change Password (Authenticated) IMPLEMENTED
**Status:** FULLY IMPLEMENTED
**Implementation:**
- change_password(old_password, new_password): verifies old password + validates new + revokes ALL sessions
**Code Reference:** auth_services.py:341-369

### Rate Limiting on Login Endpoint IMPLEMENTED
**Status:** FULLY IMPLEMENTED
**Implementation:**
- auth_controller.py:62 has `@RATE_LIMIT_LOGIN` decorator
- Limits: 5 requests per 15 minutes per IP
**Code Reference:** auth_controller.py:47-72

### Session Management UI IMPLEMENTED
**Status:** FULLY IMPLEMENTED
**Operations:**
- `GET /api/v1/auth/sessions` - List all sessions for authenticated user (auth_controller.py:320-356)
- `POST /api/v1/auth/sessions/{session_id}/revoke` - Revoke specific session (auth_controller.py:358-388)
- `POST /api/v1/auth/sessions/revoke-all` - Revoke all sessions except current (auth_controller.py:390+)
**Code Reference:** auth_controller.py:320-400

### MFA Backup Code Comparison
**Status:** IMPLEMENTED (sequential comparison)
**Concern:** All backup codes compared one-by-one (timing attack potential)
**Mitigation:** Using constant-time comparison via bcrypt (salted hash comparison)
**Risk Level:** MINIMAL (bcrypt timing-safe by design)

---

## Testing & Validation

### Tests Implemented

✅ **Integration Tests:** `test_mfa_login_flow.py`
- Signup flow with email verification
- Login with MFA enabled → temporary token (5min TTL)
- MFA verification with TOTP
- MFA verification with backup code
- Login with MFA disabled → full tokens

✅ **Unit Tests & Additional Coverage:**
- Password reset (send + reset with token validation)
- Password change (with old password verification + session revocation)
- Token refresh with JTI rotation
- Logout with session revocation
- Email verification token expiration (24h TTL)
- Rate limiting (auth_controller.py endpoint decorated with @RATE_LIMIT_LOGIN)
- Session management (list, revoke specific, revoke all)

---

## Architecture Decisions

**ADR-001: Credential Separated from User**
- CredentialModel is 1:1 with UserModel
- Separates identity (User) from authentication (Credential)
- Enables future multi-credential support (OAuth, etc.)
- Reference: `back/docs/architecture/decisions/ADR-001-credential-separated-from-user.md`

**ADR-004: Clean Architecture Adapted**
- Services use ORM Models directly (no separate Domain Entities yet)
- BaseRepository pattern for generic CRUD
- Reference: `back/docs/architecture/decisions/ADR-004-clean-architecture-adapted.md`

---

## Infrastructure & Background Jobs

### Session Cleanup Job

**Status:** IMPLEMENTED  
**Purpose:** Automatically delete expired sessions to prevent database bloat

**Implementation:**
- Job: `session_cleanup_job.py` (SessionCleanupJob)
- Method: `AuthSessionRepository.delete_expired(before: datetime)`
- Retention: 30 days (configurable)
- Runs: Daily (scheduled via RQ or cron)

**Process:**
1. Calculate cutoff date: `now - retention_days`
2. Delete sessions where `expires_at < cutoff_date`
3. Log number of sessions deleted

**Justification:**
- Prevents unlimited growth of `auth_sessions` table
- Maintains query performance
- LGPD compliance (data minimization)

**Code Reference:**
- `back/src/robbot/infra/jobs/session_cleanup_job.py`
- `back/tests/unit/services/test_session_cleanup_job.py`

### Device Fingerprinting

**Status:** IMPLEMENTED  
**Purpose:** Extract human-readable device names from User-Agent headers

**Implementation:**
- Function: `security.parse_device_name(user_agent: str) -> str`
- Fallback: `utils.extract_device_name()` (more comprehensive)
- Stored in: `auth_sessions.device_name`

**Examples:**
```python
# Input: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0"
# Output: "Chrome on Windows 10"

# Input: "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Safari/604.1"
# Output: "Safari on iPhone"
```

**Benefits:**
- Users can identify their active sessions by device
- Easier session revocation ("Revoke my iPhone session")
- Security awareness (detect unknown devices)

**Code Reference:**
- `back/src/robbot/core/security.py:17-60` (parse_device_name)
- `back/src/robbot/common/utils.py:68-120` (extract_device_name)

---

## Security Improvements (v1.1)

### 1. Unique Constraint on reset_token ✅

**Problem:** `reset_token` could theoretically be duplicated  
**Solution:** Added `unique=True` constraint to `credentials.reset_token`  
**Impact:** Prevents password reset token collision attacks  
**Migration:** `a1b2c3d4e5f6_add_unique_constraint_reset_token.py`

**Code Reference:**
- `back/src/robbot/infra/db/models/credential_model.py:49`
- `back/alembic/versions/a1b2c3d4e5f6_add_unique_constraint_reset_token.py`

### 2. Session Cleanup Automation ✅

**Problem:** Expired sessions accumulate indefinitely  
**Solution:** Background job deletes sessions expired >30 days ago  
**Impact:** Prevents table bloat, improves query performance  
**Scheduling:** Daily execution via RQ scheduler

### 3. Device Fingerprinting ✅

**Problem:** `device_name` was always `None` (poor UX)  
**Solution:** Parse User-Agent to extract browser and OS  
**Impact:** Users can identify sessions easily

### 4. Idle Timeout Implementation ✅

**Problem:** Sessions inactive indefinitely remained valid  
**Solution:** Revoke sessions inactive >30 days on refresh attempt  
**Impact:** Forces re-authentication for idle accounts (security best practice)  
**Code Reference:**
- `back/src/robbot/services/auth_services.py:208-215` (idle timeout check in refresh)
- `back/tests/unit/services/test_idle_timeout.py` (5 test scenarios)

**Behavior:**
- Tracks `last_used_at` on every token refresh
- On refresh: compares `last_used_at` vs `now - 30 days`
- If inactive >30 days: revokes session and returns `AuthException`
- User must login again to create new session
- Different from `expires_at`: inactivity timeout vs absolute expiration

---

## Known Limitations

*None - All identified incomplete logics have been resolved (v1.1)*

---

## Potential Future Enhancements (Out of Scope)

### 🟢 LOW PRIORITY

1. **Concurrent Refresh Race Condition Mitigation**  
   - Current: First request succeeds, second fails (token already revoked)
   - Enhancement: Return cached tokens for simultaneous requests
   - Estimated time: 2 hours

2. **Revoked Session Cleanup Policy**  
   - Current: Revoked sessions marked but not deleted
   - Enhancement: Delete revoked sessions after 90 days (auditability)
   - Estimated time: 30 minutes

3. **Additional Edge Case Tests**  
   - Scenario: Session hijacking attempt (wrong user_id with valid JTI)
   - Scenario: Refresh from different IP (anomaly detection)
   - Device fingerprint anomaly detection
   - Estimated time: 3-4 hours

### 🔵 NOT PLANNED

- OAuth/SAML integration (out of scope)
- SMS-based 2FA (TOTP sufficient)
- Biometric authentication (mobile-first)


