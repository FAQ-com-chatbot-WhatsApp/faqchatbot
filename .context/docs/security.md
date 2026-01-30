---
status: filled
---
# Security & Compliance: Clinica Go


**Status:** filled
**Updated:** 2026-01-27

This document provides a comprehensive overview of the security measures, authentication models, and compliance practices implemented in the **Go** system.

---

## 1. Authentication & Authorization

### User Authentication
- **Multi-Factor Authentication (MFA):** The system supports and encourages MFA for all administrative and agent accounts. Managed via `MfaService`.
- **Session Management:** handled by `AuthService` and stored in `AuthSessionModel`. Sessions have defined expiration times and can be remotely revoked.
- **Password Policies:** Secure hashing (bcrypt) is used for all stored credentials.

### RBAC (Role-Based Access Control)
Access is restricted based on user roles (e.g., `ADMIN`, `AGENT`). Permissions are enforced at both the API router level and the service layer.

---

## 2. Infrastructure Security

### Data Protection
- **Encryption in Transit:** All communication between the frontend and backend, and between our system and external services (WhatsApp/WAHA), must be conducted over TLS (HTTPS).
- **Encryption at Rest:** Sensitive database fields (where applicable) and backups are encrypted at rest.
- **Secrets Management:** Environment variables and secrets are managed outside of version control (using `.env` files locally and secure vault systems in production).

### Networking
- **WAHA Gateway:** Interactions with the WhatsApp API are proxied through the WAHA gateway, which should be protected within a private network partition or via IP allowlisting.

---

## 3. Auditing & Observability

### Audit Logging
The `AuditService` and `AuditLogRepository` record all critical actions, including:
- User logins/logouts.
- Permission changes.
- Configuration updates.
- Lead deletions or bulk modifications.

These logs are immutable and stored in the `AuditLogModel` for compliance and forensic analysis.

---

## 4. Secure Development Practices

### Input Validation
- **Backend:** Strict Pydantic schema validation for all inbound JSON payloads.
- **Frontend:** Zod-based validation for all forms to prevent malformed data from reaching the API.

### Error Handling
- Internal stack traces are never exposed in production API responses. The `normalizeApiError` function ensures that only safe, consumer-friendly error messages are displayed to users.

---

## 5. Compliance Considerations

If the clinical data handled includes PHI (Protected Health Information):
- Developers must ensure data handling adheres to local regulations (e.g., LGPD in Brazil).
- Any data export must be audited.
- User accounts must be blocked immediately upon termination of access (`block_user` endpoint).
