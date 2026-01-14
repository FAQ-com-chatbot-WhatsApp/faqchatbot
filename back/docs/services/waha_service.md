---
title: WAHA Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: WAHA Service (`waha_service.py`)

## Brief description of the requirements and goals of the feature
This service is the primary bridge between our application and the WhatsApp world. It provides a unified interface for managing WhatsApp sessions and for sending various types of messages. It encapsulates all interactions with the WAHA (WhatsApp HTTP API) client, adding crucial features like database persistence for session state and rate limiting to prevent the account from being banned.

## Architecture and design
The `WAHAService` is a high-level service that combines a database repository (`SessionRepository`) with an external API client (`WAHAClient`).

- **Session Management**:
    - It handles the full lifecycle of a WhatsApp session: creating, starting (which involves generating a QR code for pairing), stopping, restarting, and logging out.
    - It synchronizes the session state between the external WAHA instance and our application's database. For example, when a session is started, it fetches the status and QR code from WAHA and saves it to our `WhatsAppSession` table.
    - `get_or_create_default_session` is a utility function used on application startup to ensure the default session is always configured.

- **Message Sending**:
    - It provides methods to send different types of messages: `send_text`, `send_image`, `send_file`, and `send_location`.
    - Each sending method first calls `_check_rate_limit`.

- **Rate Limiting**:
    - The `_check_rate_limit` method uses Redis to enforce a limit on the number of messages sent to a single chat ID within a specific time window (e.g., messages per hour).
    - This is a critical anti-ban feature. It creates a unique Redis key for each `chat_id`, increments a counter for each message sent, and sets an expiration time on the key. If the counter exceeds the configured limit, the message is blocked.

## Tasks
- [x] Manage the complete lifecycle of WhatsApp sessions (create, start, stop, status).
- [x] Synchronize session state with the application's database.
- [x] Send various types of messages (text, image, file, location).
- [x] Implement a Redis-based rate-limiting mechanism to prevent account bans.
- [x] Provide methods to get session status and QR codes for pairing.
- [x] Handle session logout (unlinking the device).

## Open questions
1. The rate limit is configured per hour (`WAHA_MESSAGES_PER_HOUR`). Is this the most effective strategy, or should other limits (e.g., per minute, or a more complex "burst" allowance) be considered?
2. How does the application handle a failed message send after the rate limit check has passed? Is there a retry mechanism?
3. The `apply_anti_ban` flag is passed to the `waha_client`. What specific "anti-ban" techniques does the underlying client implement?
