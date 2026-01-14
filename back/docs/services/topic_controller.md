---
title: "Topic Controller (topic_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Topic Controller (`topic_controller.py`)

## 1. Description
The Topic Controller manages the highest-level organizational structure for content: Topics. Topics act as categories or folders for grouping related playbooks. For example, a "Facial Procedures" topic might contain playbooks for "Botox," "Fillers," and "Chemical Peels." This controller provides a full set of CRUD endpoints for managing these topics.

## 2. Architecture and Design
This controller follows a standard RESTful resource-oriented design for managing the `Topic` entity.
- **Service Abstraction:** It delegates all business logic to the `PlaybookService`. Although the resource is `Topic`, the `PlaybookService` is designed to be a comprehensive service that manages the entire playbook ecosystem, including topics and steps.
- **CRUD Endpoints:** It provides the standard `POST`, `GET`, `PATCH`, and `DELETE` endpoints for creating, retrieving, updating, and deleting topics.
- **Filtering and Pagination:** The `GET /` endpoint for listing topics includes parameters for filtering by `active_only` status and for `skip`/`limit` pagination.

## 3. Data Structure
- **`TopicCreate` & `TopicUpdate`**: Pydantic schemas for creating and updating topics. They include fields like `name`, `description`, `category`, and `active`.
- **`TopicOut`**: The response schema for a single topic.
- **`TopicList`**: A schema for returning a paginated list of topics along with a `total` count.
- **`DeletedResponse`**: A generic schema for confirming successful deletions.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the routes under the `/topics` prefix.
- **`robbot.services.playbook_service.PlaybookService`**: The service layer that handles all database operations for topics.
- **`robbot.core.security.get_current_user`**: Ensures all endpoints are authenticated.
- **`sqlalchemy.orm.Session`**: Injected via `get_db` to provide a database session to the service.

## 5. Use Cases
- **Use Case 1: Organizing Content:** An administrator wants to create a new category for all weight-loss-related playbooks. They call `POST /` to create a new topic named "Weight Loss."
- **Use Case 2: Populating a Dropdown Menu:** The frontend needs to display a list of available topics so the user can assign a new playbook to one. It calls `GET /?active_only=true` to get a list of all active topics.
- **Use Case 3: Deleting a Category:** An administrator decides to remove the "Seasonal Promotions" topic and all its associated playbooks. They call `DELETE /{topic_id}`. The `PlaybookService` handles the cascading delete, removing the topic, all playbooks within it, and all their steps.

## 6. Security
- **Authentication:** All endpoints are protected by the `get_current_user` dependency, ensuring that only logged-in users can manage topics.
- **Cascading Deletes:** The `DELETE` operation is powerful and destructive. The application relies on the database's foreign key constraints with `ondelete="CASCADE"` to ensure data integrity, but this means the action is irreversible and should be protected by strong frontend confirmations.

## 7. Performance
- The performance of the list endpoint (`GET /`) depends on the database indexing of the `topics` table.
- Since deleting a topic can trigger a large number of cascading deletes (playbooks, steps, vector embeddings), this operation could be slow and potentially lock tables. For systems with a very large number of playbooks, it might be better to perform this deletion in a background job.

## 8. Testability
- The controller can be unit-tested by mocking the `PlaybookService`.
- Integration tests are crucial for the `DELETE` endpoint to ensure that the cascading deletion behavior works as expected and that all related entities (playbooks, steps, and vector embeddings) are properly removed.

## 9. Error Handling
- **Not Found:** The controller raises `404 Not Found` exceptions if a client tries to get, update, or delete a topic that does not exist.
- **Validation Errors:** FastAPI and Pydantic handle validation of incoming payloads, returning `422 Unprocessable Entity` for any malformed requests.
