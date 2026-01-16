# Feature: User Login (Authentication)

**Epic:** Authentication & Sessions  
**Status:** IMPLEMENTED  
**Owner:** Backend Team  
**Code:** auth_services.py:87-187

## What's Implemented

### authenticate_user() - Lines 87-187

**Input:** email, password, [user_agent], [ip_address]

**Process:**
1. Lookup user by email (UserRepository.get_by_email())
2. Check user.is_active (blocks inactive users)
3. Check email_verified (blocks unverified users, raises AuthException)
4. Verify password via credential_svc.verify_password() (bcrypt comparison)
5. Check MFA enablement (via credential_repo.get_by_user_id())
6. **If MFA enabled (lines 156-167):**
   - Create temporary tokens (5min TTL, type="mfa-pending")
   - Return Token(access_token=temporary, refresh_token="", mfa_required=True)
   - No session created yet
7. **If MFA disabled (lines 169-187):**
   - Create access + refresh tokens
   - Extract JTI from refresh_token
   - Create AuthSessionModel (jti, ip_address, user_agent, device_name, expires_at)
   - Log audit: "login_success"
   - Return Token(access_token, refresh_token, mfa_required=False)
8. On password failure: Log audit "login_failure", return None

**Output:** Token (MFA pending or full) OR None on failure

**Raises:** AuthException if email not verified

**Code Reference:** auth_services.py:87-187

## Key Checks

✅ Email verification blocks login (line 114-117)
✅ User active check (line 110-112)
✅ Password comparison via bcrypt (line 119-120)
✅ MFA branching (lines 145-187)
✅ Session created on no-MFA login (lines 174-186)
✅ Rate limiting: @RATE_LIMIT_LOGIN decorator (5 per 15min per IP)
✅ Device fingerprinting: device_name extracted via security.parse_device_name() (line 177)

## Models

- **AuthSessionModel:** user_id, refresh_token_jti, ip_address, user_agent, device_name, expires_at, is_revoked
- **Device Name Examples:** "Chrome on Windows 10", "Safari on iPhone"

## Testing

✅ Unverified email blocks login
✅ Inactive user blocked
✅ Wrong password returns None
✅ MFA enabled → temporary token
✅ MFA disabled → full tokens + session
✅ Rate limiting enforced (5 per 15 min)
✅ Device name parsed from User-Agent
