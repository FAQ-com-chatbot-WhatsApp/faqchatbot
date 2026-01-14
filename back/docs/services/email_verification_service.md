---
title: Email Verification Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Email Verification Service (`email_verification_service.py`)

## Brief description of the requirements and goals of the feature
This service manages the process of verifying a user's email address. This is a standard and crucial feature for any application with user registration, as it ensures that the provided email is valid and owned by the user. This prevents spam, and fake accounts, and ensures that users can receive important communications (like password resets).

## Architecture and design
The `EmailVerificationService` works closely with the `CredentialRepository` to manage verification state, which is stored in the `credentials` table.

- **Token Generation (`generate_verification_token`)**:
    1.  When a user registers (or requests a new verification email), this method is called.
    2.  It uses Python's `secrets` module to generate a cryptographically strong, URL-safe random token.
    3.  It stores this token and the current timestamp (`email_verification_sent_at`) in the user's `Credential` record.
    4.  The token is then sent to the user's email address (this sending logic is likely handled elsewhere, e.g., in `AuthService`).

- **Verification (`verify_email`)**:
    1.  When a user clicks the verification link in their email, the endpoint they hit will call this method with the token from the URL.
    2.  The service finds the credential record associated with that specific token.
    3.  It performs several checks:
        - If the token is invalid (not found).
        - If the email is already verified.
        - If the token has expired (by comparing the `email_verification_sent_at` timestamp with a configured expiration time).
    4.  If all checks pass, it sets the `email_verified` flag to `true` and nullifies the token to prevent it from being used again.

- **Resending and Status Checks**:
    - **`resend_verification_email`**: Allows a user to request a new verification email. It includes a rate-limiting check to prevent abuse, ensuring a user must wait a minimum amount of time between requests.
    - **`is_email_verified`**: A simple helper method used by other services (like `AuthService` during login) to check if a user is allowed to proceed.

## Tasks
- [x] Generate a secure, unique verification token for a user.
- [x] Store the token and its creation time in the database.
- [x] Verify a token, checking for validity, expiration, and prior use.
- [x] Mark an email as verified upon successful token validation.
- [x] Provide a rate-limited mechanism for resending verification emails.
- [x] Offer a simple method to check if a user's email is verified.

## Open questions
1. The actual sending of the email is not part of this service. Which service is responsible for constructing the email body (with the verification link) and sending it?
2. What is the current expiration time for the verification token (`EMAIL_VERIFICATION_TOKEN_EXPIRATION_HOURS`)?
3. If a user changes their email address, does this trigger a new verification flow for the new address?
