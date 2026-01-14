---
title: Credential Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Credential Service (`credential_service.py`)

## Brief description of the requirements and goals of the feature
This service has a single, critical responsibility: to manage user passwords securely. It is designed to completely isolate password handling from the main user data, following security best practices. Its goals are to securely set, verify, and change user passwords without ever exposing the plain text password to other parts of the system.

## Architecture and design
The `CredentialService` is a highly specialized service that acts as a secure vault for passwords. It is a key component of the architecture decision `ADR-001-credential-separated-from-user`.

- **`CredentialRepository`**: It uses this repository to interact with the `credentials` table in the database, which stores the `user_id` and the `hashed_password`, but no other user information.

- **`security` module**: It relies heavily on the `security` module for all cryptographic operations:
    - `validate_password_policy`: Before setting or changing a password, it ensures the new password meets the application's complexity requirements.
    - `get_password_hash`: It uses this function (which should implement a strong, salted hashing algorithm like bcrypt) to hash the plain text password before storing it.
    - `verify_password`: It uses this function to compare a provided plain text password against the stored hash in a way that is safe from timing attacks.

**Key Methods:**
- **`set_password`**: This is the primary method for setting or updating a password. It validates the password, hashes it, and then either creates a new credential record or updates an existing one.
- **`verify_password`**: This method is used during the login process. It takes a user ID and a password, retrieves the corresponding hash from the database, and uses the secure comparison function to check if they match.
- **`change_password`**: This method allows an authenticated user to change their password. It first uses `verify_password` to confirm the user's current password before allowing them to set a new one via `set_password`.

## Tasks
- [x] Securely set or update a user's password, including validation and hashing.
- [x] Verify a plain text password against its stored hash.
- [x] Provide a secure method for users to change their own password.
- [x] Isolate all password-related logic from other user management services.

## Open questions
1. What is the specific password policy being enforced by `security.validate_password_policy` (e.g., length, character types, etc.)?
2. The `AuthException` is raised in several places. How is this generic exception handled at the API layer to provide clear feedback to the user without revealing security details?
3. Is there any mechanism to detect if a user is trying to reuse an old password?
