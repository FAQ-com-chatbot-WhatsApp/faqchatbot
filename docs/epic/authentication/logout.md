# Feature: User Logout & Session Termination

**Epic:** Authentication & Sessions  
**Status:** IMPLEMENTED  
**Owner:** Backend Team  
**Code:** auth_services.py:248-290

## What's Implemented

### logout() - Lines 248-290

**Input:** user_id, [access_token], [refresh_token]

**Process:**
1. If access_token provided: revoke via token_repo.revoke()
2. If refresh_token provided:
   - Revoke token via token_repo.revoke()
   - Decode refresh token to extract JTI (lines 272-276)
   - Lookup AuthSessionModel by JTI via session_repo.get_by_jti()
   - Mark session revoked: session_repo.revoke(sess, reason="logout")
3. Log audit: "logout" event (lines 282-289)

**Output:** None (side effects: revoked tokens, revoked session)

**Raises:** No exceptions (silent if token invalid)

**Code Reference:** auth_services.py:248-290

## Session Revocation

- AuthSessionModel.is_revoked = true
- AuthSessionModel.revoked_at = now()
- AuthSessionModel.revocation_reason = "logout"
- Token also added to revoked list (TokenRepository)

## Important Notes

- Only ONE logout() method exists (no separate logout_session/logout_all)
- Revocation is immediate (session cannot be reused)
- Both access and refresh tokens revoked
- Audit trail records all logouts

## Testing

✅ logout() called from api/controllers/auth_controller.py POST /auth/logout
✅ Session marked revoked after logout
✅ Tokens added to revoked list
✅ Audit log records logout event
✅ IP address + User-Agent tracked in session
