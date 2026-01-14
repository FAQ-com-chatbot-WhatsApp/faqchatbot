
# Feature: NotificationService

## 1. Description

The `NotificationService` manages the platform's internal (in-app) notification system. It is responsible for creating, displaying, and managing the lifecycle of notifications directed at system users, such as administrators and agents. The goal is to keep users informed about important events occurring in the application in real-time.

## 2. Architecture and Design

- **Layered Architecture:** The service follows the project's adapted Clean Architecture pattern. It acts as the business logic layer, orchestrating notification operations and using the `NotificationRepository` for data persistence.
- **Dependency Injection:** The service receives a SQLAlchemy session (`db: Session`) in its constructor, which facilitates testability and database transaction management.
- **Specialized Methods:** In addition to a generic `create_notification` method, the service offers high-level methods for specific business events (e.g., `notify_new_lead`, `notify_urgent_message`). This makes the code more readable and centralizes message formatting logic.

## 3. Data Structure

The service operates on the `NotificationModel`, which has the following main fields:
- `id` (UUID): Unique identifier for the notification.
- `user_id` (int): ID of the user who will receive the notification.
- `notification_type` (str): Category of the notification (e.g., `NEW_LEAD`, `URGENT_MESSAGE`).
- `title` (str): Title of the notification.
- `message` (str): Detailed content.
- `is_read` (bool): Flag indicating whether the notification has been read.
- `created_at` (datetime): Creation timestamp.

## 4. Dependencies and Integrations

- **`NotificationRepository`:** Data access layer that abstracts CRUD operations in the database for the `NotificationModel`.
- **SQLAlchemy (`Session`):** Used to interact with the PostgreSQL database.
- **Other Services:** Several other services invoke the `NotificationService` to generate alerts:
    - `LeadService`: May call `notify_new_lead` when a new lead is assigned to an agent.
    - `HandoffService`: May call `notify_transfer_received` when a conversation is transferred.
    - `ConversationOrchestrator`: May call `notify_urgent_message` if the `IntentDetector` identifies an urgent message.

## 5. Use Cases

- **Notify about New Lead:** When a new patient makes contact and is qualified as a lead, the responsible agent is notified so they can follow up.
- **Urgent Message Alert:** If a patient mentions words like "severe pain" or "emergency," the system creates a high-priority notification to ensure a quick response.
- **Receiving a Transfer:** When one agent transfers a conversation to another, the recipient receives a notification to take control of the chat.
- **Display on Dashboard:** The user interface (frontend) consumes the API endpoints that use `get_user_notifications` and `count_unread` to display a notification panel and an unread items counter.

## 6. Security

- Operations are always linked to a `user_id`, ensuring that a user can only see and interact with their own notifications. Access to the API endpoints that trigger these functions must be protected by authentication and authorization.

## 7. Performance

- **Optimized Queries:** The `NotificationRepository` should have database indexes on the `user_id` and `is_read` columns to ensure that listing and counting unread notifications are fast, even with a large volume of data.
- **Pagination/Limit:** The `get_user_notifications` function includes a `limit` parameter to avoid overloading the system when fetching an excessive number of notifications at once.

## 8. Testability

- The service is highly testable. In unit tests, the database session (`db`) and the `NotificationRepository` can be mocked to verify that the creation and update methods are called with the correct parameters for each type of business event.

## 9. Error Handling

- **Notification Not Found:** When trying to mark a notification as read, if the provided ID does not exist, a `NotFoundException` is thrown, resulting in an HTTP 404 response in the API.

