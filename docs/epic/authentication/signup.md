# Feature: User Signup (Registration)

**Epic:** Authentication & Sessions  
**Status:** IMPLEMENTED  
**Owner:** Backend Team  
**Code:** auth_services.py:48-86

## What's Implemented

### signup() - Lines 48-86

**Input:** SignupRequest(email, password, full_name, role)

**Process:**
1. Check email uniqueness via UserRepository.get_by_email()
2. Validate password policy (min 8 chars, complexity)
3. Hash password with bcrypt (via security.get_password_hash())
4. Create UserModel (email_verified=false)
5. Create CredentialModel via credential_svc.set_password()
6. Generate email verification token via email_verification_svc
7. Log audit: "signup" event
8. Return UserOut (without password)

**Output:** UserOut (id, email, full_name, role, created_at)

**Raises:** AuthException if email exists or password invalid

**Code Reference:** auth_services.py:48-86

## Models

- **UserModel:** id, email (unique), full_name, is_active, role, email_verified
- **CredentialModel:** user_id (FK), password_hash, mfa_enabled, mfa_secret, backup_codes

## Testing

✅ signup() called from api/controllers/auth_controller.py POST /auth/signup
✅ Email uniqueness check works
✅ Password validation works
✅ Verification email sent
✅ Unverified users cannot login
