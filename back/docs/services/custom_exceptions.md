
# Core Feature: Custom Exceptions

## 1. Description

The `custom_exceptions.py` file defines a hierarchy of custom exception classes for the application. The goal is to create a more semantic and robust error-handling system, where each type of error that can occur in the system has its own exception class. This allows the upper layers of the application (such as API middlewares) to catch specific types of errors and return appropriate HTTP responses and clear error messages, instead of handling generic exceptions.

## 2. Architecture and Design

-   **Exception Hierarchy:** All custom exceptions inherit from a base class `RobbotError`. This allows for creating a generic `try...except RobbotError` to catch any known application error, while also allowing more specific errors (like `NotFoundException` or `AuthException`) to be caught when necessary.
-   **Semantic Categorization:** Exceptions are grouped by domain:
    -   **Base:** Fundamental errors like `AuthException`, `NotFoundException`, `BusinessRuleError`.
    -   **External Services (`ExternalServiceError`):** A base class for errors in third-party services, with specific subclasses like `LLMError` (for Gemini), `WAHAError` (for WhatsApp), and `VectorDBError` (for ChromaDB). This makes it easier to monitor which external integration is failing.
    -   **Data:** Errors related to data manipulation, such as `ValidationError` and `ExportError`.
    -   **System:** Configuration errors (`ConfigurationError`) and job execution errors (`JobError`).
-   **Additional Context:** Exceptions like `ExternalServiceError` and `JobError` are designed to carry additional context, such as the name of the service that failed (`service_name`) and the original exception (`original_error`), which is extremely useful for debugging and logging.

## 3. Data Structure

-   The exceptions are classes that inherit from `Exception`. They do not have a complex data structure, but their constructors are designed to accept and store contextual information that can be logged or displayed.

## 4. Dependencies and Integrations

-   This module has no external dependencies.
-   **Integration:** It is integrated throughout the application. Virtually all services and repositories can raise one of these exceptions instead of generic Python exceptions.
-   **API Middleware:** A middleware in FastAPI (not shown in the file, but implied in the architecture) likely catches these exceptions and maps them to HTTP status codes. For example:
    -   `NotFoundException` -> `HTTP 404 Not Found`
    -   `AuthException` -> `HTTP 401 Unauthorized` or `HTTP 403 Forbidden`
    -   `BusinessRuleError`, `ValidationError` -> `HTTP 400 Bad Request` or `HTTP 422 Unprocessable Entity`
    -   `ExternalServiceError` -> `HTTP 503 Service Unavailable` or `HTTP 502 Bad Gateway`

## 5. Use Cases

-   **Resource Not Found:** A service tries to fetch a conversation by an ID that does not exist. The repository raises a `NotFoundException`. The API middleware catches it and returns a `404` response with a clear message.
-   **Gemini API Failure:** The `IntentDetector` tries to call the Gemini API, but the call fails due to a network issue. The Gemini client raises an `LLMError`, providing the service name ("Gemini") and the original error message. This is logged with details, allowing the development team to know exactly which external service failed.
-   **Business Rule Violation:** The `ConversationService` attempts an invalid status transition (e.g., from `CLOSED` to `TRANSFERRED`). It raises a `BusinessRuleError`. The middleware catches it and returns a `400` error, informing the client that the operation is not allowed.

## 6. Security

-   By catching specific exceptions, the system avoids leaking implementation details and stack traces to the end-user, which is an important security practice. Only curated and safe error messages are returned.

## 7. Performance

-   The use of custom exceptions has no significant performance impact compared to standard Python exceptions. The benefit in terms of maintainability, debugging, and code robustness outweighs any minimal overhead.

## 8. Testability

-   Code that raises custom exceptions is easy to test. Using `pytest.raises`, one can assert that a specific function raises the correct type of exception under the expected error conditions.

## 9. Error Handling

-   This module is, in itself, the backbone of the application's error handling. It provides the tools for the rest of the code to implement a clear, consistent, and informative error-handling strategy.

