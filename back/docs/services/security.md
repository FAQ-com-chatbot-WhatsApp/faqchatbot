
# Core Feature: Security

## 1. Description

`security.py` is the central module for all application security features. It handles password encryption, the creation and validation of JSON Web Tokens (JWT), and the logic for protecting API endpoints, ensuring that only authenticated users can access them.

## 2. Architecture and Design

-   **Password Hashing with `passlib`:** Uses the `passlib` library with `CryptContext` configured to use the `bcrypt` algorithm. `bcrypt` is an industry standard for password hashing because it is slow and resistant to brute-force attacks. The `get_password_hash` function generates the hash, and `verify_password` compares it with a plain-text password.
-   **JWT-based Authentication:** The system uses JWT for stateless authentication.
    -   **Token Creation:** The `create_token_for_subject` function is a generic token generator. `create_access_refresh_tokens` uses it to create a pair of tokens:
        1.  **Access Token:** A short-lived token (e.g., 15 minutes), used to authenticate most API requests.
        2.  **Refresh Token:** A long-lived token (e.g., 7 days), used to obtain a new access token without the user needing to log in again. It contains a JTI (JWT ID) to allow session tracking and revocation.
    -   **Token Validation:** The `decode_token` function encapsulates the logic for decoding and validating a token, checking the signature, algorithm, and expiration date.
-   **FastAPI Dependency (`get_current_user`):** The `get_current_user` function is a FastAPI dependency. When an endpoint is decorated with `Depends(get_current_user)`, FastAPI executes this function before the route logic. It extracts the token from the `Authorization: Bearer ...` header, validates it, and if valid, returns the payload with user information (like `user_id`). If the token is invalid, it raises an `HTTPException` with a `401 Unauthorized` status, blocking access to the route.
-   **Device Name Extraction:** The `parse_device_name` function is a utility that parses the `User-Agent` string to extract a readable device and browser name (e.g., "Chrome on Windows"). This is useful for session management, allowing the user to see where their logins are coming from.

## 3. Data Structure

-   **JWT Payload:** The token payload is a JSON dictionary containing standardized "claims":
    -   `sub` (Subject): The user's identifier (e.g., `user_id`).
    -   `exp` (Expiration Time): The timestamp of when the token expires.
    -   `iat` (Issued At): The timestamp of when the token was created.
    -   `type`: The token type ("access" or "refresh").
    -   `jti` (JWT ID): A unique identifier for the refresh token, used to manage sessions.

## 4. Dependencies and Integrations

-   **`pyjwt`:** The library used to encode and decode JWTs.
-   **`passlib` and `bcrypt`:** Used for password hashing.
-   **`FastAPI`:** The module is deeply integrated with FastAPI's dependency injection system.
-   **`robbot.config.settings`:** Obtains critical security settings, such as the `SECRET_KEY` (secret key for signing JWTs), the `ALGORITHM` (e.g., "HS256"), and token expiration times.
-   **API Endpoints:** All endpoints requiring authentication use `Depends(get_current_user)` in their signature.

## 5. Use Cases

-   **Password Hashing on Registration:** When a new user registers, their plain-text password is passed to `get_password_hash` before being saved to the database.
-   **Password Verification on Login:** During login, the password provided by the user is compared with the hash stored in the database using `verify_password`.
-   **Token Generation on Login:** If the password is valid, `create_access_refresh_tokens` is called to generate a new pair of tokens that are returned to the client.
-   **Accessing a Protected Route:** The client makes a request to a protected endpoint, including the access token in the `Authorization` header. `get_current_user` validates the token. If valid, the route logic is executed. If invalid or expired, a `401` response is returned.

## 6. Security

-   This module is the heart of the application's security.
-   The separation between short-lived access tokens and long-lived refresh tokens is a recommended security practice. It limits the exposure time of an access token if it is compromised.
-   Validating the token type (`payload.get("type") != "access"`) in `get_current_user` prevents a refresh token from being used to access the API directly.

## 7. Performance

-   Hash verification with `bcrypt` is intentionally slow to hinder brute-force attacks.
-   JWT validation is a very fast cryptographic operation based on HMAC and has a negligible performance impact on requests.

## 8. Testability

-   Pure functions like `get_password_hash`, `verify_password`, `create_token_for_subject`, and `decode_token` are easy to unit test.
-   The `get_current_user` dependency can be tested in integration tests by making requests to a protected endpoint with valid, invalid, and expired tokens and checking if the correct HTTP responses are returned.

## 9. Error Handling

-   **`AuthException`:** A custom exception used internally for token errors (expired, invalid).
-   **`HTTPException`:** `get_current_user` translates the `AuthException` into an `HTTPException` with a `401 Unauthorized` status, which is what FastAPI uses to generate the final HTTP response. This decouples the business logic (raising `AuthException`) from the API presentation logic (returning `HTTPException`).

