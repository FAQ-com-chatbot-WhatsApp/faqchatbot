---
title: "Conversation Controller (conversation_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Conversation Controller (`conversation_controller.py`)

## 1. Description
The Conversation Controller manages the lifecycle and state of all patient conversations. It provides a comprehensive set of REST endpoints for listing, viewing, searching, updating, and exporting conversations. This controller is central to the agent-facing dashboard, allowing staff to monitor, manage, and interact with ongoing patient dialogues.

## 2. Architecture and Design
This controller acts as the primary interface for all conversation-related actions.
- **Service Delegation:** It delegates all business logic to the `ConversationService`, adhering to the Clean Architecture principle of separating API concerns from business rules.
- **Rich Filtering:** Endpoints like `GET /conversations` support extensive filtering via query parameters (`status`, `urgent_only`, `assigned_to_me`), allowing for powerful and flexible queries from the frontend.
- **State Management:** It exposes endpoints for explicit state transitions, such as transferring (`/transfer`) or closing (`/close`) a conversation, ensuring that all state changes are controlled and audited.
- **Data Export:** It includes an `/export` endpoint that generates and streams a CSV file, demonstrating handling of different response types.

## 3. Data Structure
- **`ConversationOut`**: The main Pydantic schema for representing a single conversation in API responses.
- **`ConversationListOut`**: A schema for paginated lists of conversations, including the list itself and a `total` count.
- **Request Schemas**: Several specific schemas for updates, such as `UpdateStatusRequest`, `TransferRequest`, and `UpdateNotesRequest`, ensuring that incoming data for mutations is well-defined and validated.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the routes under the `/conversations` prefix.
- **`robbot.services.conversation_service.ConversationService`**: The service layer that handles all logic related to conversations.
- **`robbot.core.security.get_current_user`**: Ensures all endpoints are authenticated.
- **`sqlalchemy.orm.Session`**: Injected via `get_db` to provide a database session to the service.
- **`robbot.domain.enums.ConversationStatus`**: Used to validate and parse status-related inputs.

## 5. Use Cases
- **Use Case 1: Agent Views Their Queue:** An agent logs in and the frontend calls `GET /conversations?assigned_to_me=true`. The controller returns a list of conversations assigned specifically to that agent.
- **Use Case 2: Transferring a Conversation:** An AI-managed conversation reaches a high maturity score. A human agent is notified, reviews the conversation by calling `GET /conversations/{id}`, and then transfers it to a secretary by calling `POST /conversations/{id}/transfer` with the secretary's user ID.
- **Use Case 3: Exporting Data for a Report:** An administrator needs a report of all conversations closed in the last month. They call `GET /conversations/export?status=CLOSED&start_date=...&end_date=...`, which streams a CSV file for download.

## 6. Security
- **Authentication:** All endpoints are protected by the `get_current_user` dependency.
- **Authorization:** The `/export` endpoint has logic to restrict non-admin users to exporting only their own conversations, demonstrating fine-grained access control.
- **Input Validation:** Pydantic schemas and FastAPI's `Query` validation prevent invalid data from being processed. For example, `limit` is constrained to a reasonable range (`ge=1, le=100`).

## 7. Performance
- **Pagination:** The `list_conversations` endpoint uses `limit` and `offset` for efficient pagination, preventing the server from loading and serializing thousands of records at once.
- **Full-Text Search:** The `/search` endpoint uses `ilike` for basic text searching. For larger-scale applications, this could be upgraded to use PostgreSQL's dedicated full-text search capabilities for better performance.
- **Streaming Response:** The `/export` endpoint uses FastAPI's `StreamingResponse`, which is memory-efficient for generating large files as it sends the data in chunks without loading the entire file into memory.

## 8. Testability
- The controller can be unit-tested by mocking the `ConversationService` and `get_current_user` dependencies.
- Integration tests are crucial for this controller to verify that the filtering, pagination, state transitions, and export functionalities work correctly with a real database session.

## 9. Error Handling
- **Invalid Input:** The controller raises `HTTPException` with a `400 Bad Request` status for invalid inputs, such as an unrecognized `status` string.
- **Not Found:** When a specific conversation is requested but does not exist, a `404 Not Found` exception is raised.
- **Invalid State Transitions:** The `ConversationService` raises `ValueError` for invalid state changes (e.g., trying to close an already closed conversation), which the controller catches and converts into a `400 Bad Request` HTTP response.
