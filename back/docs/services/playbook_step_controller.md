---
title: "Playbook Step Controller (playbook_step_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Playbook Step Controller (`playbook_step_controller.py`)

## 1. Description
The Playbook Step Controller is responsible for managing the individual steps within a playbook. Each step represents a single message or action in the conversational script. This controller provides endpoints to add, list, reorder, update, and delete these steps, giving administrators fine-grained control over the content and flow of each playbook.

## 2. Architecture and Design
This controller is designed to manage the details of a playbook's composition.
- **Master-Detail Relationship:** The endpoints are designed around a master-detail relationship with playbooks. Most endpoints operate on steps belonging to a specific playbook (`/playbook/{playbook_id}`).
- **Bulk Operations:** The `POST /reorder` endpoint is designed for efficient bulk updates. It accepts a list of step IDs and their new order, allowing a frontend client to reorder an entire playbook with a single API call.
- **Specialized Views:** It provides two different "list" endpoints:
    - `GET /playbook/{playbook_id}`: A simple list of steps.
    - `GET /playbook/{playbook_id}/details`: An enriched view that includes the full content of the associated message. This is optimized for consumption by the AI (LLM), which needs the complete message content to execute the playbook.

## 3. Data Structure
- **`PlaybookStepCreate` & `PlaybookStepUpdate`**: Pydantic schemas for adding and modifying steps.
- **`PlaybookStepOut`**: The standard response schema for a single step.
- **`PlaybookStepList`**: A schema for returning a list of steps with a total count.
- **`PlaybookStepReorder`**: A specific schema for the bulk reordering endpoint, expecting a list of `[step_id, new_order]` tuples.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the routes under the `/playbook-steps` prefix.
- **`robbot.services.playbook_service.PlaybookService`**: This controller delegates all its logic to the `PlaybookService`, which handles the underlying database operations for playbook steps.
- **`robbot.core.security.get_current_user`**: Ensures all endpoints are authenticated.
- **`sqlalchemy.orm.Session`**: Injected via `get_db` to provide a database session to the service.

## 5. Use Cases
- **Use Case 1: Building a Playbook:** An administrator is building a playbook. They call `POST /` multiple times to add steps, each linked to a pre-existing message. They then use a drag-and-drop interface in the frontend, which calls `POST /reorder` to save the final sequence.
- **Use Case 2: Executing a Playbook:** The `PlaybookOrchestration` service needs to execute a playbook. It calls `GET /playbook/{playbook_id}/details` to get the complete, ordered list of steps with all message content, which it then processes one by one.
- **Use Case 3: Removing a Step:** An administrator decides a message is no longer relevant to a playbook. They call `DELETE /{step_id}` to remove that specific step from the sequence. The `PlaybookService` handles the deletion and re-indexes the parent playbook.

## 6. Security
- **Authentication:** All endpoints are protected by the `get_current_user` dependency.
- **Data Integrity:** The `reorder` endpoint includes logic to ensure that all steps being reordered belong to the same playbook, preventing data corruption.

## 7. Performance
- The `reorder` endpoint performs a bulk update within a single database transaction, which is much more efficient than updating each step individually with separate API calls.
- The `/details` endpoint may involve multiple database joins to fetch the step and its associated message details. The performance of this endpoint should be monitored, and caching could be added if it becomes a bottleneck.

## 8. Testability
- The controller can be unit-tested by mocking the `PlaybookService`.
- Testing the `reorder` endpoint is particularly important to ensure the logic for validating that all steps belong to the same playbook works correctly.
- Integration tests should verify that adding, reordering, or deleting a step correctly updates the playbook and triggers the re-indexing process.

## 9. Error Handling
- **Not Found:** The controller raises `404 Not Found` exceptions if a client tries to operate on a step or playbook that does not exist.
- **Bad Request:** The `reorder` endpoint raises a `400 Bad Request` if the list of steps is empty, preventing an invalid operation.
- **Generic Errors:** A `500 Internal Server Error` is returned if the reordering process fails for an unexpected reason.
