---
title: "Audit Controller (audit_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Audit Controller (`audit_controller.py`)

## 1. Description
The Audit Controller provides REST endpoints for accessing audit trail logs. It is a critical component for security, compliance, and debugging, allowing administrators to track all significant actions performed within the system. The endpoints are strictly protected and accessible only by users with an 'ADMIN' role.

## 2. Architecture and Design
This controller is part of the API Layer and is designed with a security-first approach.
- **Role-Based Access Control (RBAC):** It uses the `get_current_user` dependency to obtain the authenticated user and explicitly checks if the user's role is `Role.ADMIN` before allowing access.
- **Service Layer Delegation:** All business logic for retrieving and filtering logs is delegated to the `AuditService`. The controller's responsibility is limited to handling HTTP requests, validating permissions, and serializing responses.
- **Query Parameters:** It utilizes FastAPI's `Query` for powerful and validated filtering of audit logs directly from the URL.

## 3. Data Structure
- **`AuditLogOut`**: The Pydantic schema used to serialize audit log records for API responses. It ensures that database models are not directly exposed and provides a consistent data contract. It includes fields like `user_id`, `action`, `entity_type`, `entity_id`, `old_value`, `new_value`, and `ip_address`.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the routes `/audit-logs` and `/audit-logs/entity/{entity_type}/{entity_id}`.
- **`robbot.core.security.get_current_user`**: A dependency to ensure that all requests are authenticated and to retrieve the current user's details for authorization checks.
- **`robbot.services.audit_service.AuditService`**: The service layer that contains the logic for querying audit logs from the database.
- **`sqlalchemy.orm.Session`**: Injected via `get_db` to provide a database session to the `AuditService`.

## 5. Use Cases
- **Use Case 1: General Monitoring:** An administrator calls `GET /audit-logs` to view the most recent activities across the entire system, helping to monitor for suspicious or unusual behavior.
- **Use Case 2: Investigating a Specific Entity:** After a user reports an issue with a specific lead (e.g., "my lead's status changed unexpectedly"), an administrator calls `GET /audit-logs/entity/lead/{lead_id}` to retrieve the complete history of that lead, showing who changed what and when.

## 6. Security
- **Admin-Only Access:** This is the most critical security aspect. Every endpoint explicitly checks for the `Role.ADMIN` and raises a `403 Forbidden` exception if the check fails.
- **Authentication:** All endpoints depend on `get_current_user`, ensuring that no unauthenticated access is possible.
- **Data Exposure:** The controller uses a dedicated output schema (`AuditLogOut`) to avoid exposing sensitive internal model details.

## 7. Performance
- The database queries performed by the `AuditService` are indexed on `entity_type`, `entity_id`, and `user_id` to ensure efficient retrieval of logs, even with a large volume of data.
- The `limit` parameter in the endpoints prevents excessive data from being fetched and serialized in a single request, protecting the system from performance degradation.

## 8. Testability
- **Unit Testing:** The controller can be tested by mocking the `get_current_user` and `AuditService` dependencies. This allows for testing the permission logic (e.g., verifying that non-admin users receive a 403 error) and the request/response flow.
- **Integration Testing:** Tests can be written to simulate API calls with a test database, verifying that the correct audit logs are created by other services and can be retrieved through these endpoints.

## 9. Error Handling
- **Permission Errors:** If a non-admin user attempts to access the endpoints, an `HTTPException` with status code `403 Forbidden` is raised.
- **General Errors:** Any other exceptions during the process are caught and re-raised as a generic `500 Internal Server Error`, preventing internal implementation details from leaking.
