---
title: User Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: User Service (`user_service.py`)

## Brief description of the requirements and goals of the feature
This service is responsible for managing the users of the web application (i.e., the clinic administrators and agents), not the end-users (patients). It provides standard CRUD (Create, Read, Update, Delete) operations for user profiles and includes administrative actions like blocking and unblocking accounts.

## Architecture and design
The `UserService` is a straightforward service that provides a business logic layer on top of the `UserRepository`. It also integrates with the `AuthSessionRepository` and `AuditService` for more complex administrative actions.

- **CRUD Operations**:
    - `list_users`: Retrieves a paginated list of all users, which is essential for user management dashboards.
    - `get_user`: Fetches a single user by their ID.
    - `update_user`: Allows updating a user's profile information, such as their full name or active status.

- **Administrative Actions**:
    - `deactivate_user`: A simple method to mark a user as inactive.
    - **`block_user`**: A more comprehensive administrative action. It not only sets the user's `is_active` flag to `false` but also immediately revokes all of their active sessions by calling `session_repo.revoke_all_for_user`. This is a critical security measure to ensure that a blocked user is logged out from all devices instantly. It also logs this significant event using the `AuditService`.
    - **`unblock_user`**: The reverse of the block action. It reactivates the user's account and logs the event.

The service ensures that user data is returned using the `UserOut` Pydantic schema, which prevents sensitive information (like password hashes) from being accidentally exposed through the API.

## Tasks
- [x] List all users with pagination.
- [x] Retrieve a single user's profile.
- [x] Update a user's profile information.
- [x] Deactivate a user's account.
- [x] Block a user, which includes revoking all their sessions and creating an audit log.
- [x] Unblock a user and log the action.

## Open questions
1. The service has methods for `deactivate_user` and `block_user`, both of which set `is_active` to `false`. What is the functional difference between these two actions?
2. Is there a "delete user" function, or is deactivation the standard procedure for removing users? If so, how is this handled in compliance with data privacy regulations like GDPR (right to be forgotten)?
3. The `update_user` method only allows changing `full_name` and `is_active`. How are other fields, like the user's role, managed after creation?
