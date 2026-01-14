---
title: Audit Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Audit Service (`audit_service.py`)

## Brief description of the requirements and goals of the feature
This service provides a centralized mechanism for logging critical actions within the application. Its purpose is to create an immutable audit trail of all significant events, such as entity creation, updates, deletions, and security-related actions (like login attempts or permission changes). This is essential for security, compliance (like LGPD/GDPR), and debugging.

## Architecture and design
The `AuditService` is a straightforward service that acts as a wrapper around the `AuditLogRepository`.

- **`log_action`**: This is the core method. It takes details about an action—who performed it (`user_id`), what the action was (`action`), what entity was affected (`entity_type`, `entity_id`), and the state of the data before (`old_value`) and after (`new_value`) the action. It serializes the `old_value` and `new_value` dictionaries into JSON strings and persists them to the `audit_logs` table in the database.

- **Convenience Methods**:
    - `log_create`, `log_update`, and `log_delete` are helper methods that simplify the process of logging common CRUD operations. They call `log_action` with the appropriate action type.

- **Log Retrieval**:
    - The service also provides methods for querying the audit logs:
        - `get_user_logs`: Retrieves all actions performed by a specific user.
        - `get_entity_logs`: Retrieves the complete history of actions for a specific entity (e.g., all changes made to a particular lead).
        - `get_recent_logs`: Gets the most recent log entries, typically for an admin dashboard.

This service is designed to be called from other services whenever a significant action occurs. For example, the `AuthService` calls `log_action` to record successful or failed login attempts.

## Tasks
- [x] Log any generic action with detailed context.
- [x] Provide specific helper methods for logging standard CRUD (Create, Update, Delete) operations.
- [x] Store the "before" and "after" state of data for update operations.
- [x] Record the user responsible for the action and their IP address.
- [x] Provide methods to retrieve audit logs by user, by entity, or by recent activity.

## Open questions
1. The `old_value` and `new_value` are stored as JSON strings. Is there a performance concern with serializing/deserializing large objects, and should there be a size limit?
2. Are all sensitive operations throughout the application consistently logged? Is there a checklist or a standardized way to ensure new features are integrated with the `AuditService`?
3. For compliance purposes, is there a data retention policy for audit logs? Are they archived or deleted after a certain period?
