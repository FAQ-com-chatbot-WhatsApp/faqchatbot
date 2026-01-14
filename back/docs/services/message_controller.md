---
title: "Message Controller (message_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Message Controller (`message_controller.py`)

## 1. Description
The Message Controller provides a complete set of CRUD (Create, Read, Update, Delete) endpoints for managing individual messages. It is designed to handle different types of messages (text, media, location) in a unified way. Additionally, it includes a specialized endpoint for leveraging AI to automatically generate descriptions for media messages.

## 2. Architecture and Design
This controller follows a standard RESTful resource-oriented design.
- **Polymorphic Payloads:** The `create_message` and `update_message` endpoints are designed to accept polymorphic payloads using a union of Pydantic schemas (`MessageCreateText | MessageCreateMedia | MessageCreateLocation`). This allows the API to handle different message types through a single endpoint, with FastAPI and Pydantic managing the validation based on the payload's content.
- **Service Layer Abstraction:** All database interactions and business logic are fully abstracted away in the `MessageService`. The controller's role is to handle HTTP requests, call the appropriate service method, and serialize the response.
- **AI Integration:** The `/generate-description` endpoint demonstrates how a controller can integrate multiple services. It uses the `MessageService` to fetch the message and the `DescriptionService` to perform the AI-powered analysis.

## 3. Data Structure
- **`MessageCreate*`, `MessageUpdate*`, `MessageOut*` schemas**: A set of Pydantic schemas is defined for each message type (Text, Media, Location) and for each operation (Create, Update, Out). This provides strong typing and validation for the API. For example, `MessageCreateMedia` includes fields for `file` and `caption`, while `MessageCreateLocation` includes `latitude` and `longitude`.
- **`DeletedResponse`**: A generic schema for confirming successful deletions.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the routes under the `/messages` prefix.
- **`robbot.services.message_service.MessageService`**: The primary service for all CRUD operations on messages.
- **`robbot.services.description_service.DescriptionService`**: Used specifically by the `/generate-description` endpoint for AI-based content analysis.
- **`robbot.core.security.get_current_user`**: Ensures all endpoints are authenticated.
- **`sqlalchemy.orm.Session`**: Injected via `get_db` to provide a database session to the services.

## 5. Use Cases
- **Use Case 1: Creating a Text Message:** A user sends a text message. The system calls `POST /messages` with a payload like `{"type": "text", "text": "Hello, world!"}`.
- **Use Case 2: Retrieving a Message:** The frontend needs to display the details of a specific message. It calls `GET /messages/{message_id}` to retrieve the full message object.
- **Use Case 3: Auto-Describing an Image:** An administrator uploads an image to be used in a playbook. After the message is created, the system calls `POST /messages/{message_id}/generate-description`. The controller uses Gemini Vision to analyze the image and returns a suggested title, description, and tags, which the administrator can then save.

## 6. Security
- **Authentication:** All endpoints are protected by the `get_current_user` dependency, ensuring that only logged-in users can interact with messages.
- **Data Integrity:** By using specific Pydantic schemas for creation and updates, the controller ensures that only valid and expected data can be written to the database.

## 7. Performance
- The performance of the CRUD endpoints is largely dependent on the database indexing for the `messages` table, particularly on the primary key (`id`).
- The `/generate-description` endpoint involves a network call to the Google Gemini Vision API, which can be slow. This operation should ideally be handled asynchronously by a background worker to avoid blocking the client, although in this implementation it appears to be synchronous.

## 8. Testability
- The controller can be unit-tested by mocking the `MessageService`, `DescriptionService`, and `get_current_user` dependencies.
- Polymorphic endpoints require thorough testing to ensure that payloads for each message type are correctly validated and processed.
- Integration tests should cover the full lifecycle of a message: creation, retrieval, update, AI description generation, and deletion.

## 9. Error Handling
- **Not Found:** The `get_message`, `update_message`, and `delete_message` endpoints will raise a `404 Not Found` error if a message with the given ID does not exist. This is handled by the service layer and propagated by the controller.
- **Validation Errors:** If a client sends a malformed payload (e.g., a text message payload to the media update endpoint), FastAPI and Pydantic will automatically return a `422 Unprocessable Entity` response with detailed validation errors.
