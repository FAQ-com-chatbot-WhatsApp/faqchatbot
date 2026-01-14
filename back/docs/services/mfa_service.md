---
title: Multi-Factor Authentication (MFA) Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: MFA Service (`mfa_service.py`)

## Brief description of the requirements and goals of the feature
This service adds a critical layer of security by managing Multi-Factor Authentication (MFA) for user accounts. It handles the entire MFA lifecycle, including setup (generating secrets and backup codes), verification of Time-based One-Time Passwords (TOTP), and disabling MFA. The goal is to protect user accounts from unauthorized access even if their passwords are compromised.

## Architecture and design
The `MfaService` is a self-contained component that integrates with the `CredentialService` and `AuthService`. It uses the `pyotp` library for TOTP generation and verification and `passlib` for securely hashing and verifying backup codes.

- **Setup (`setup_mfa`)**:
    1.  Generates a unique, random secret key for the user (using `pyotp.random_base32`).
    2.  Creates a `otpauth://` URI, which is a standard format that authenticator apps (like Google Authenticator or Authy) can read to set up the account.
    3.  Generates a list of single-use backup codes for recovery purposes. These codes are hashed with `bcrypt` before being stored as a JSON string in the database.
    4.  Saves the MFA secret and the hashed backup codes to the user's `Credential` record and marks MFA as enabled.
    5.  Returns the secret, a representation of the QR code, and the plain text backup codes to the user for them to save.

- **Verification**:
    - **`verify_mfa`**: This is the primary verification method. It takes a user-provided TOTP code and uses `pyotp` to check if it's valid for the user's secret, allowing for a small time window to account for clock drift.
    - **`verify_backup_code`**: If a user loses their authenticator device, they can use a backup code. This method iterates through the stored hashes of the backup codes, securely checks for a match using `bcrypt.verify`, and if a match is found, it "consumes" the code by removing it from the list in the database to prevent reuse.

- **Disabling (`disable_mfa`)**: This method simply removes the MFA secret and backup codes from the user's credential record and sets the `mfa_enabled` flag to `false`.

## Tasks
- [x] Generate a unique TOTP secret and provisioning URI for a user.
- [x] Generate a secure set of single-use backup codes.
- [x] Enable MFA for a user by storing the secret and hashed backup codes.
- [x] Verify a user-provided TOTP code.
- [x] Verify a user-provided backup code and consume it after use.
- [x] Disable MFA for a user.

## Open questions
1. The `otpauth_uri` is returned as a base64 encoded string. Is the frontend responsible for converting this into a visible QR code image for the user to scan?
2. When a user consumes their last backup code, are they prompted to generate new ones, or is MFA automatically disabled?
3. What is the process for a user who has lost both their authenticator device and their backup codes? Is there an administrator-led recovery flow?
