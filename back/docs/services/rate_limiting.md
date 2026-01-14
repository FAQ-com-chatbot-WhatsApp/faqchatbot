
# Core Feature: Rate Limiting

## 1. Description

`rate_limiting.py` implements a rate limiting system for the API endpoints, using Redis as the backend. Its main objective is to protect the application against abuse, brute-force attacks (e.g., multiple login attempts), and ensure service stability by preventing a single client from overwhelming the system with an excessive number of requests.

## 2. Architecture and Design

-   **Sliding Window Algorithm:** The implementation uses a simple and effective sliding window approach based on counters in Redis. For each identifier and endpoint, a key is created in Redis with an expiration time (`window_seconds`). With each request, the counter is incremented. If the counter exceeds `max_requests` before the key expires, the request is blocked.
-   **Flexible Key Strategies:** The system supports multiple strategies for identifying a client:
    -   `ip`: Uses the client's IP address. Ideal for unauthenticated endpoints.
    -   `user`: Uses the authenticated user's `user_id` (extracted from the request state, which is populated by the security middleware).
    -   `email`: Extracts the email from the JSON request body. Useful for endpoints like "forgot my password".
-   **Decorator for FastAPI:** The primary way to use it is through the `@rate_limit` decorator, which can be applied directly to FastAPI route functions. This makes applying rate limiting declarative and easy to maintain.
-   **Singleton Pattern:** A global instance of `RateLimiter` is created and managed through the `init_rate_limiter` and `get_rate_limiter` functions. This ensures that the connection to Redis is reused throughout the application.
-   **Fail-Open:** In case of a communication failure with Redis, the rate limiter adopts a "fail-open" strategy, meaning it allows the request to pass. This ensures application availability even if the rate limiting system is temporarily unavailable.

## 3. Data Structure

-   **Keys in Redis:** Keys are stored in the format `ratelimit:{key_type}:{endpoint}:{identifier_hash}`. The identifier (IP, email) is hashed to ensure privacy and avoid storing sensitive data in plain text in Redis.
-   **Values in Redis:** The value associated with each key is a simple counter (integer).

## 4. Dependencies and Integrations

-   **`Redis`:** A fundamental dependency for storing rate limiting counters.
-   **`FastAPI` (`Request`):** The decorator interacts deeply with FastAPI's `Request` object to extract the identifier (IP, user state) and the endpoint path.
-   **Authentication Middleware (implied):** For the `key_type="user"` strategy, the system relies on an authentication middleware that must run beforehand and populate `request.state.user_id` with the authenticated user's ID.

## 5. Use Cases

-   **Login Protection:** The login endpoint (`/auth/login`) is decorated with `@RATE_LIMIT_LOGIN`. This limits the same IP to 5 login attempts every 15 minutes, mitigating brute-force attacks.
-   **Password Recovery Limitation:** The password recovery endpoint is decorated with `@RATE_LIMIT_PASSWORD_RECOVERY`, limiting the same email to 3 requests per hour to prevent spam.
-   **Access Control for Authenticated Endpoints:** An endpoint that performs a costly operation can be limited by user (`key_type="user"`) to ensure fair use and prevent a single user from overloading the system.
-   **HTTP 429 Response:** When a client exceeds the limit, the decorator raises an `HTTPException` with the status `429 Too Many Requests`. The response includes useful headers like `Retry-After`, informing the client when they can try again.

## 6. Security

-   This module is a fundamental security feature.
-   Hashing identifiers in Redis keys is a good privacy practice, especially for IP addresses.

## 7. Performance

-   Redis operations (INCR, EXPIRE, TTL) are atomic and extremely fast, ensuring that the rate limiting overhead on each request is minimal.

## 8. Testability

-   The `RateLimiter` can be unit-tested with a mocked Redis client (`fakeredis`).
-   One can simulate a sequence of requests and assert that:
    -   The first requests (within the limit) are allowed.
    -   The request that exceeds the limit is blocked (i.e., an `HTTPException` is raised).
    -   After the time window expires, a new request is allowed.

## 9. Error Handling

-   The main error handling strategy is "fail-open": if Redis is unavailable, an exception is logged, but the request is allowed. This prioritizes API availability over rate limiting protection.
-   The code also handles edge cases, such as when it cannot extract an identifier (falling back to "unknown") or when the `Request` object is not available.

