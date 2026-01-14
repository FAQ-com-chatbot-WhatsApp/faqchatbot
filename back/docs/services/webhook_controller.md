---
title: "WAHA Webhook Controller (webhook_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: WAHA Webhook Controller (`webhook_controller.py`)

## 1. Description
The Webhook Controller is the public-facing entry point for all events originating from the WAHA (WhatsApp HTTP API) gateway. Its primary role is to receive incoming webhook payloads, log them for persistence and auditability, and then enqueue the relevant events (specifically, incoming messages) into a background job queue for asynchronous processing. This decouples the immediate response to the webhook from the potentially time-consuming work of processing the message with an AI.

## 2. Architecture and Design
This controller is designed for high availability and rapid response.
- **Asynchronous Processing:** The core design principle is "accept, log, and enqueue." The endpoint immediately acknowledges the webhook with a `202 Accepted` status code and then passes the payload to a background queue. This ensures the WAHA gateway receives a fast response and doesn't time out, even if the AI processing is slow.
- **No Authentication:** This is a critical design choice. The endpoint is intentionally public because webhook providers like WAHA cannot typically be configured to send authentication headers. Security is managed at the infrastructure level (e.g., firewall rules, VPN) to ensure that only the WAHA container can call this endpoint.
- **Resilience:** By logging every incoming webhook to the database *before* enqueueing, the system creates a durable record. If the queueing process fails, the log can be used for manual or automated reprocessing.

## 3. Data Structure
- **`WebhookPayload`**: A Pydantic schema that defines the expected structure of an incoming webhook from WAHA. It includes the `event` type (e.g., "message," "session.status") and the nested `payload` data.
- **`WebhookLogOut`**: The response schema, which returns the details of the log entry created in the database.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the `/webhooks/waha` route.
- **`robbot.services.queue_service.get_queue_service`**: A function to get an instance of the queue service, which is used to enqueue message processing jobs.
- **`robbot.adapters.repositories.webhook_log_repository.WebhookLogRepository`**: A repository for creating webhook log entries in the database.
- **Logging**: The standard Python logging module is used extensively to provide detailed, structured logs about the webhook processing flow.

## 5. Use Cases
- **Use Case 1: Receiving a Patient Message:** A patient sends a WhatsApp message. WAHA forwards this as a `POST` request to `/webhooks/waha` with an `event` of "message." The controller logs the entire payload, extracts the message data, enqueues it for processing by the `ConversationOrchestrator`, and immediately returns a `202 Accepted` response to WAHA.
- **Use Case 2: Receiving a Session Status Update:** The WAHA session disconnects. WAHA sends a webhook with an `event` of "session.status." The controller logs this event for debugging and monitoring purposes but does not enqueue it for further processing.
- **Use Case 3: Debugging a Failed Message:** A developer notices a message was not processed. They call the internal `GET /webhooks/waha/logs` endpoint to view recent webhook logs, find the one that failed, and analyze its payload to understand the issue.

## 6. Security
- **Public Endpoint:** As noted, this endpoint is public. The documentation explicitly states that it must be protected at the network level in a production environment.
- **No Sensitive Data in Response:** The endpoint only returns the database log of the webhook, not the result of the processing, preventing data leakage.

## 7. Performance
- **High Throughput:** The "accept and enqueue" pattern makes this endpoint extremely fast and capable of handling a high volume of incoming webhooks without getting bogged down by the AI processing time.
- **Database Write:** The only blocking operation is the initial database write to create the webhook log. This is a very fast, indexed operation.

## 8. Testability
- The controller can be tested by sending mock `WebhookPayload` objects to a `TestClient`.
- Tests should verify that:
    - A `202 Accepted` status is returned.
    - A `WebhookLog` entry is created in the test database.
    - The `queue_service.enqueue_message_processing` method is called with the correct data when the event is "message."
    - The queue service is *not* called for other event types.

## 9. Error Handling
- **Robust Logging:** The controller is wrapped in extensive `try...except` blocks. Any failure during the logging or enqueueing process is caught and logged in detail, including the specific webhook ID and error message. This is crucial for debugging a system that relies on asynchronous processing.
- **No HTTP Errors to Client:** The endpoint is designed to almost always return a `202 Accepted` status to the webhook provider, even if internal processing fails. Failures are handled internally through logging and potential retry mechanisms, which is a common pattern for resilient webhook consumers.
