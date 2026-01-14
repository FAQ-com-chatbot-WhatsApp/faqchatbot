---
title: "Playbook Controller (playbook_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Playbook Controller (`playbook_controller.py`)

## 1. Description
The Playbook Controller provides the API endpoints for managing playbooks. Playbooks are structured, reusable conversation scripts designed to guide patients through specific topics (e.g., "Weight Loss," "Botox Information"). This controller handles the full CRUD lifecycle for playbooks and, most importantly, exposes a semantic search endpoint that leverages a vector database (ChromaDB) for intelligent retrieval.

## 2. Architecture and Design
This controller is a central part of the content management system for the chatbot.
- **CRUD Operations:** It provides standard RESTful endpoints for creating, retrieving, updating, and deleting playbooks.
- **Semantic Search:** The `GET /search` endpoint is a key feature. It takes a natural language query, passes it to the `PlaybookService`, which then queries ChromaDB to find the most semantically relevant playbooks. This allows the AI to find appropriate scripts even if the user's query doesn't exactly match the playbook's name or description.
- **Service Abstraction:** All logic, including interactions with the main database and the ChromaDB vector store, is handled by the `PlaybookService`.

## 3. Data Structure
- **`PlaybookCreate` & `PlaybookUpdate`**: Pydantic schemas for creating and updating playbooks.
- **`PlaybookOut`**: The response schema for a single playbook, including its ID, name, description, and associated topic.
- **`PlaybookList`**: A schema for returning a list of playbooks with a total count.
- **`PlaybookSearchResults`**: A specialized schema for the search endpoint, which includes the playbook data along with a relevance score.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the routes under the `/playbooks` prefix.
- **`robbot.services.playbook_service.PlaybookService`**: The primary service that encapsulates all business logic for playbooks, including database CRUD and semantic search.
- **`robbot.core.security.get_current_user`**: Ensures all endpoints are authenticated.
- **`sqlalchemy.orm.Session`**: Injected via `get_db` to provide a database session to the service.

## 5. Use Cases
- **Use Case 1: Creating a New Conversation Flow:** An administrator wants to create a new script for "Dental Whitening." They first create a "Dental Whitening" topic, then call `POST /playbooks` with the new playbook's name and description, linking it to the topic.
- **Use Case 2: AI Finds a Relevant Playbook:** A patient sends a message: "quanto custa clarear os dentes?" The `ConversationOrchestrator` calls `GET /playbooks/search?query=clareamento+dental+preço`. The controller returns the "Dental Whitening" playbook, ranked high by relevance, which the orchestrator then uses to respond to the patient.
- **Use Case 3: Updating a Playbook:** An administrator needs to update the description of a playbook. They call `PATCH /playbooks/{playbook_id}` with the new description. The `PlaybookService` updates the record in the database and automatically re-indexes the playbook in ChromaDB to ensure future searches reflect the new content.

## 6. Security
- **Authentication:** All endpoints are protected by the `get_current_user` dependency, ensuring that only authorized users can manage playbooks.
- **Input Validation:** FastAPI and Pydantic validate all incoming data, such as ensuring the `top_k` parameter for search is within a reasonable range (`1-10`).

## 7. Performance
- **Semantic Search:** The performance of the `/search` endpoint depends on the efficiency of the ChromaDB vector store. As the number of playbooks grows, maintaining the health and performance of the vector index is crucial.
- **Database Indexing:** The `playbooks` table in the main database should be indexed on `topic_id` to ensure that listing playbooks by topic (`GET /topic/{topic_id}`) is fast.

## 8. Testability
- The controller can be unit-tested by mocking the `PlaybookService`.
- Testing the `/search` endpoint requires a mock `PlaybookService` that returns predefined search results to verify that the controller correctly serializes the `PlaybookSearchResults` schema.
- Integration tests should verify that creating or updating a playbook correctly triggers the re-indexing process in the (test) vector database.

## 9. Error Handling
- **Not Found:** The controller raises `404 Not Found` exceptions if a client tries to get, update, or delete a playbook that does not exist.
- **Invalid Query:** The `/search` endpoint enforces a `min_length` on the query parameter, and FastAPI will automatically return a `422 Unprocessable Entity` error if the query is too short.
