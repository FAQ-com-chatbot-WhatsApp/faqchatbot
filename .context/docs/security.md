# Security Policy and Implementation Guide

This document outlines the security policies, architectural principles, and developer guardrails for the Clinica Go application. Adherence to these guidelines ensures the confidentiality, integrity, and availability (CIA) of data and compliance with relevant protection regulations.

Security controls are baked into the development lifecycle (Secure SDLC), and all services, particularly the Python/Go backend, strictly follow the Principle of Least Privilege (PoLP).

---

## 1. Core Security Guardrails

### 1.1. Audit Logging

Mandatory logging for all security-relevant events, including authentication changes, session activities, administrative actions, and critical data access.

| Component | Responsibility | Details |
| :--- | :--- | :--- |
| `AuditService` | Service Layer | Handles the creation and processing of audit events. |
| `AuditLogRepository` | Persistence Layer | Ensures audit records are persisted immutably in the database. |
| `AuditLogModel` / `AuditLogOut` | Data Structure | Defines the schema for stored and retrieved audit log entries. |

**Developer Action:** Ensure that any endpoint or function modifying user permissions, system configuration, or sensitive data explicitly calls the `AuditService` before and after the action.

### 1.2. Input Validation and Sanitization

All data received from external sources (API requests, webhooks) must be validated and sanitized to prevent common injection attacks (XSS, SQLi) and system abuse.

| Layer | Implementation | Mechanism |
| :--- | :--- | :--- |
| **Frontend** | `frontend\src\lib\validations` | Client-side schema validation using libraries like Zod/Yup (e.g., `SignInValues`, `SignUpValues`). |
| **Backend API** | Pydantic Schemas | Strict validation of incoming JSON payloads against defined Pydantic models (e.g., schemas in `back\src\robbot\schemas`). |
| **Robustness** | Middleware | The `add_request_size_limit` middleware actively limits the size of request bodies to prevent Denial-of-Service (DoS) attacks via oversized payloads. |

### 1.3. Secure Communication (TLS/HTTPS)

All communication, both external and internal, must be encrypted.

*   **External Traffic:** All public-facing endpoints are accessible exclusively over HTTPS (TLS 1.2+).
*   **Internal Service Mesh:** Inter-service communication (e.g., between Python FastAPI services and Go workers) must use TLS encryption, typically HTTPS or gRPC over TLS.

### 1.4. Dependency Management

The project uses automated scanning in the CI/CD pipeline to detect and mitigate Common Vulnerabilities and Exposures (CVEs) in third-party dependencies.

**Policy:** Developers must ensure all dependencies are up-to-date. Critical and high-severity vulnerabilities flagged by security tooling must be addressed immediately by upgrading or replacing the library.

---

## 2. Authentication and Session Management

The backend handles identity and sessions via dedicated services and repositories.

### 2.1. Centralized Auth Service

The `AuthService` is the single source of truth for user authentication.

| Component | Function | Security Implication |
| :--- | :--- | :--- |
| `AuthService` | Handles login, session generation, and token validation. | Ensures consistent security checks across all endpoints. |
| `AuthSessionModel` / `AuthSessionRepository` | Database models for active user sessions. | Enables immediate, server-side session revocation (`logout` functionality). |
| Tokens | Short-Lived JWTs | Access tokens are short-lived and passed via `Authorization` headers (`auth_headers`). |
| Idle Timeout | Session termination | Sessions automatically expire after a period of inactivity, reducing the attack surface (tested in `test_idle_timeout.py`). |

### 2.2. Multi-Factor Authentication (MFA)

MFA using TOTP is supported and strongly recommended.

*   **Setup:** The `MfaService` manages the registration and validation process, including the generation of one-time `BackupCodesResponse` for recovery.
*   **Validation:** Login flows, such as those validated in `TestMfaLoginFlow`, enforce the second factor.

### 2.3. User Security Management

The `UserService` provides capabilities to manage user security incidents rapidly.

*   **Blocking:** The `block_user` controller endpoint (requiring a `BlockUserRequest` schema) allows administrators to immediately disable a user account in response to suspicious activity.

---

## 3. Authorization (Role-Based Access Control - RBAC)

Authorization logic dictates what an authenticated user is permitted to do. This is enforced at the controller level based on assigned roles.

### 3.1. Role Definitions

| Role | Primary Functions | Context Fixture |
| :--- | :--- | :--- |
| **Admin** | Full system control: user management, configuration, auditing. | `authenticated_admin` |
| **Secretary** | Operational user: lead processing, conversation handling, playbook management. | `authenticated_secretary` |
| **Read-Only** | Viewing performance metrics, dashboards, and reports only. | (Internal fixture/user type) |

### 3.2. Enforcement

Authorization checks must be performed in the **Adapter Controllers** before calling the core business logic services.

**Example RBAC Check Structure (Conceptual):**

```python
# In a controller method (e.g., in user_controller.py)
async def block_user(user_id: str, current_user: User = Depends(get_current_active_user)):
    # 1. AUTHENTICATION (Handled by middleware/dependency)
    
    # 2. AUTHORIZATION (RBAC check)
    if not auth_service.has_permission(current_user, Permission.MANAGE_USERS):
        raise ForbiddenException("Insufficient privileges")
        
    # 3. BUSINESS LOGIC
    return await user_service.block_user(user_id)
```

---

## 4. Data Protection and Secrets Management

### 4.1. Secrets Management

Secrets (API keys, database credentials) are never hardcoded and must be handled by secure, external systems in production environments.

*   **Development Environment:** Non-sensitive settings use environment variables (`.env`).
*   **Production Environment:** Secrets are fetched at runtime from a secure vault (e.g., HashiCorp Vault, AWS Secrets Manager).
*   **Usage:** Services like `WAHAService` and integrations with AI models (e.g., `Gemini`) must retrieve their required keys from configuration which is injected via the secure loading process.

### 4.2. Data at Rest Encryption

Sensitive data must be encrypted when stored.

*   **Database Encryption:** The underlying database infrastructure must provide transparent data encryption (TDE) or volume-level encryption.
*   **Application-Level Encryption:** For fields classified as **Sensitive** (e.g., PHI, external integration credentials), strong application-level encryption (e.g., AES-256 GCM) must be applied before persistence.

### 4.3. Data Classification

Developers must be aware of the data they handle:

| Classification | Sensitivity | Required Controls Summary |
| :--- | :--- | :--- |
| **Sensitive** | Highest (Level 3) | Encrypted at rest, mandatory audit logging, strict RBAC. |
| **Internal** | Medium (Level 2) | Restricted access (internal users only), standard encryption. |
| **Public** | Lowest (Level 1) | Standard controls. |

---

## 5. Incident Response (IR) Summary

In the event of a suspected security breach, developers must follow the defined Incident Response procedure:

1.  **Report:** Immediately notify the Incident Commander and Security Team via the designated high-priority channel.
2.  **Triage:** Incident Commander assesses scope and severity.
3.  **Containment:** The affected system or service must be isolated (e.g., disabling compromised users via `block_user`, revoking sessions, network segmentation).
4.  **Eradication:** Deploy patches or configuration fixes to eliminate the vulnerability.
5.  **Recovery:** Restore normal service operations and verify integrity.
6.  **Analysis:** Conduct a post-incident review using the immutable records provided by the `AuditLogRepository` to identify root causes and implement preventative measures.
