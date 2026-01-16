# Feature: Token Refresh & Rotation

**Epic:** Authentication & Sessions  
**Status:** IMPLEMENTED  
**Owner:** Backend Team  
**Code:** auth_services.py:188-240

## What's Implemented

### refresh() - Lines 188-240

**Input:** refresh_token, [user_agent], [ip_address]

**Process:**
1. Check token not revoked via token_repo.is_revoked() (line 195)
2. Decode refresh_token via security.decode_token(verify_exp=True) (line 196)
3. Validate token type="refresh" (line 198-199)
4. Extract subject (user_id) from payload (line 200)
5. Extract JTI from payload (line 202)
6. Lookup session via session_repo.get_by_jti(jti) (line 203)
7. Verify session not revoked and not expired (line 204-205)
8. **Check idle timeout: if last_used_at > 30 days ago → revoke session (lines 208-215)**
9. Update session.last_used_at via session_repo.update_last_used() (line 219-225)
10. Revoke old refresh_token via token_repo.revoke() (line 226)
11. Create new token pair via security.create_access_refresh_tokens() (line 227)
12. Log audit "token_refresh" (line 228-234)
13. Return Token(access_token, refresh_token)

**Output:** Token(access_token, refresh_token, mfa_required=false)

**Raises:** AuthException if token invalid, revoked, expired, or session inactive >30 days

**Code Reference:** auth_services.py:188-245

## Idle Timeout Check (v1.1)

✅ Implemented: Sessions inactive for >30 days are automatically revoked
✅ Comparison: `last_used_at` vs `datetime.now(UTC) - timedelta(days=30)`
✅ Message: Clear user-friendly error about 30-day inactivity
✅ Revoking: Session marked `is_revoked=True` and JTI stored
✅ Recovery: User must login again to create new session

**Note:** Idle timeout = inactivity period (no API calls). Token expiration (7 days) is separate.

## Session Update

- session.last_used_at updated on each refresh (line 219)
- Optional device metadata update (user_agent, ip_address, device_name)
- Session must be non-revoked and not expired
- Session must be active (< 30 days since last use)

## Token Rotation

✅ Old refresh_token revoked (one-time use enforced)
✅ New refresh_token created with new JTI
✅ New access_token created (15min TTL)

## Testing

✅ Valid refresh token generates new access token
✅ Invalid refresh token raises error
✅ Expired refresh token raises error
✅ Revoked refresh token raises error
✅ Session last_used_at updated
✅ Audit log records token_refresh event
