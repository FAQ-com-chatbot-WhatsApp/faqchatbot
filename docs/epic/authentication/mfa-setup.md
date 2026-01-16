# Feature: MFA Setup

**Epic:** Authentication & Sessions  
**Status:** IMPLEMENTED  
**Owner:** Backend Team  
**Code:** mfa_service.py:1-105

## What's Implemented

### MfaService (mfa_service.py)

**setup_mfa(user_id) - Complete MFA initialization**

**Process:**
1. Generate TOTP secret (pyotp.random_base32(), 32 chars)
2. Create OTPAuth URI for QR code (otpauth://totp/...)
3. Generate 10 backup codes (8 hex chars each via secrets.token_hex(4))
4. Hash backup codes with bcrypt
5. Store in CredentialModel: mfa_enabled=false initially (not enabled until verified)
6. Return: secret, QR code (base64), backup codes (plain text, shown once)

**verify_mfa(user_id, code) - Validate TOTP code**

**Process:**
1. Lookup credential.mfa_secret
2. Create TOTP object via pyotp.TOTP(secret)
3. Verify code with ±30s time window (valid_window=1)
4. Raise AuthException if invalid

**verify_backup_code(user_id, code) - One-time backup code validation**

**Process:**
1. Lookup credential.backup_codes (JSON array of bcrypt hashes)
2. Compare provided code against each hash (bcrypt timing-safe)
3. If match found: remove from list, save back to DB (one-time enforcement)
4. Raise AuthException if not found or already used

**disable_mfa(user_id) - Turn off MFA**

**Process:**
1. Set credential.mfa_enabled = false
2. Optionally clear secret and codes

## Code References

- setup_mfa(): mfa_service.py:32-59
- verify_mfa(): mfa_service.py:60-75
- verify_backup_code(): mfa_service.py:76-97
- disable_mfa(): mfa_service.py:98-105
- _generate_secret(): mfa_service.py:9-10
- _generate_backup_codes(): mfa_service.py:17-28

## Models

- **CredentialModel:** mfa_enabled (bool), mfa_secret (string), backup_codes (JSON array)

## Standards

- **Algorithm:** HMAC-SHA1 (RFC 6238)
- **Time Step:** 30 seconds
- **Digits:** 6 (user enters 6-digit code)
- **Secret:** Base32 encoded (RFC 4648)

## Testing

✅ TOTP secret generated correctly
✅ TOTP code validated with ±30s window
✅ 10 backup codes generated
✅ Backup codes are one-time use
✅ Backup codes hashed before storage
