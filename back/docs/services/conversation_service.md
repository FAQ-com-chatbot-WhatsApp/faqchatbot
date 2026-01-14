
# Feature: ConversationService

## 1. Description

The `ConversationService` is responsible for the business logic that governs the lifecycle of a conversation. It handles operations for creating, updating status, transferring, and closing conversations, acting as an orchestration layer that is separate from the AI logic (which resides in the `ConversationOrchestrator`).

## 2. Architecture and Design

-   **Separation of Concerns:** This service strictly focuses on business rules and the state of the conversation (status, assignment, notes), while the `ConversationOrchestrator` handles the conversation flow, message processing, and interaction with the AI.
-   **State Machine:** The service implements a state machine for the conversation status through the `_is_valid_transition` method. This ensures that a conversation can only transition between allowed states (e.g., `ACTIVE` -> `TRANSFERRED`), preventing data inconsistencies.
-   **Dependency Injection:** It receives a SQLAlchemy session (`db: Session`) to interact with the database, which decouples the service from the persistence layer and facilitates testing.
-   **Lead Creation/Update:** When transferring a conversation (`transfer_to_secretary`), the service is responsible for ensuring that a corresponding `Lead` exists and is assigned to the correct user. If the lead does not exist, it is created.

## 3. Data Structure

The service primarily operates on the `ConversationModel`, which includes:
-   `id` (UUID): Conversation identifier.
-   `chat_id` (str): Chat ID in WhatsApp.
-   `status` (Enum `ConversationStatus`): The current state of the conversation (e.g., `ACTIVE`, `CLOSED`, `TRANSFERRED`).
-   `assigned_to_user_id` (int): ID of the agent/secretary to whom the conversation is assigned.
-   `notes` (str): Internal notes about the conversation.
-   `closed_at` (datetime): Timestamp of when the conversation was closed.

## 4. Dependencies and Integrations

-   **`ConversationRepository`:** Data access layer that abstracts CRUD operations for the `ConversationModel`. The service instantiates the repository on demand within its methods.
-   **`LeadModel`:** The service interacts with the `Lead` model to create or update the assignment when a conversation is transferred.
-   **`ConversationOrchestrator`:** The orchestrator uses the `ConversationService` to get or create a conversation (`get_or_create`) at the beginning of each interaction.
-   **`HandoffService`:** Likely uses `transfer_to_secretary` to effect the transfer of a conversation to a human agent.

## 5. Use Cases

-   **Starting a New Conversation:** When a new patient sends a message, `get_or_create` is called to create a new `Conversation` record in the database.
-   **Transfer to Human Support:** When the bot reaches a handoff trigger (e.g., high maturity score), `transfer_to_secretary` is invoked to change the conversation status and assign it to an available agent.
-   **Closing a Service Interaction:** After resolving the patient's request, an agent (or the system) can call the `close` method to mark the conversation as closed.
-   **Listing Conversations on the Dashboard:** The user interface uses `list_conversations` to display lists of conversations filtered by status (e.g., "Waiting for service", "Active").

## 6. Security

-   Listing and filtering operations can be restricted by `assigned_to_user_id`, ensuring that an agent only sees the conversations relevant to them. The authorization logic for this should be implemented in the API layer that consumes this service.

## 7. Performance

-   Creating repositories within each method (`repo = ConversationRepository(self.db)`) might seem inefficient, but since the repository itself is lightweight (only containing query logic), the impact is minimal. The database connection is managed by the injected and reused session (`self.db`).
-   The `list_conversations` method returns both the list of results (with `limit` and `offset`) and the total count, which is efficient for implementing pagination on the frontend.

## 8. Testability

-   The status transition logic (`_is_valid_transition`) can be tested in an isolated and exhaustive manner.
-   The service's methods can be unit-tested by mocking the `ConversationRepository` to simulate database behavior and verify that business rules (like creating a lead on transfer) are applied correctly.

## 9. Error Handling

-   **`NotFoundException`:** Thrown if an operation is attempted on a conversation that does not exist, resulting in an HTTP 404 response in the API.
-   **`BusinessRuleError`:** Thrown if an invalid status transition is attempted, which translates to an HTTP 400 (Bad Request) response, informing the client that the operation violates a business rule.

