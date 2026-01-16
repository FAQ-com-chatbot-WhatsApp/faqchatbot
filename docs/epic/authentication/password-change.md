# Feature: Password Change (Authenticated User)

**Epic:** Authentication & Sessions  
**Status:** IMPLEMENTED  
**Owner:** Backend Team  
**Code:** auth_services.py:338-372

## What's Implemented

### change_password() - Lines 338-372

**Input:** user_id, old_password, new_password

**Process:**
1. Lookup user via repo.get_by_id(user_id) (line 342-344)
2. Verify old_password via credential_svc.verify_password() (line 346-348)
   - Raises AuthException if password incorrect
3. Validate new_password policy via security.validate_password_policy() (line 350-351)
4. Update password via credential_svc.set_password(user_id, new_password) (line 352)
5. Revoke ALL user sessions via session_repo.revoke_all_for_user() (line 355)
   - reason="password_changed"
6. Log audit "password_change" (line 357-364)

**Output:** None (password changed, all sessions revoked)

**Raises:** AuthException if old password incorrect or user not found

**Code Reference:** auth_services.py:338-372

## Security Features

✅ Requires current password verification (prevents unauthorized changes)
✅ New password must meet policy (min 8 chars, complexity)
✅ All active sessions revoked after change
✅ Forces re-login with new password on all devices
✅ Audit trail records all password changes
✅ User_id required (must be authenticated to use this feature)

## Difference from Reset

| Aspect | Reset (Forgot) | Change (Authenticated) |
|--------|---|---|
| Requires old password | NO | YES |
| Valid without login | YES (15min token) | NO (requires user_id) |
| Revokes sessions | YES | YES |
| Sent via email | YES | NO |
| Audit event | password_reset | password_change |

## Testing

✅ Correct old password required
✅ Wrong old password rejected
✅ New password must meet policy
✅ All sessions revoked after change
✅ User logged out from all devices
✅ Audit log records password_change event
✅ Can be called from authenticated endpoint (PUT /auth/password)
