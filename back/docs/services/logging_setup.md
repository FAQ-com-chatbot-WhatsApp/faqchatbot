
# Core Feature: Logging Setup

## 1. Description

`logging_setup.py` is the centralized module that configures the logging system for the entire application. It establishes a structured and consistent log format, inspired by the WAHA model, and manages log output to the console and to rotating files. The goal is to have a professional, informative, and easy-to-analyze logging system, especially in multi-worker environments.

## 2. Architecture and Design

-   **Centralized Configuration:** The `configure_logging` function is the single entry point for configuring the root logger. This ensures that all modules using `logging.getLogger(__name__)` inherit the same configuration.
-   **Custom Formatters:**
    -   `StructuredFormatter`: Defines the default log format: `service_name | [HH:MM:SS.mmm] LEVEL (ModuleName/ProcessID): Message`. Including the service name (e.g., "api", "worker") and the process ID is crucial for debugging distributed or multi-process systems.
    -   `ColoredStructuredFormatter`: Inherits from the default formatter and adds ANSI colors for console output, improving readability during development. The color varies with the log level (INFO is green, ERROR is red) and the service name.
-   **Custom Filter (`MessagePrefixStripFilter`):** This filter removes prefixes like `[INFO]`, `[SUCCESS]` from the beginning of log messages. This avoids duplicating level information (which is already in the structured format) and keeps messages clean.
-   **Environment-Based Configuration:**
    -   The log level (`LOG_LEVEL`) is dynamically determined by the `ENVIRONMENT` environment variable. In production, the default level is `INFO`; in development, it is `DEBUG`.
    -   Console colorization is controlled by the `LOG_COLOR` variable.
-   **Multiple Handlers:** The system is configured with two output handlers:
    1.  `StreamHandler`: Sends logs to standard output (`stdout`), which is the recommended practice for containerized applications.
    2.  `RotatingFileHandler`: Saves logs to a file (e.g., `logs/robbot.log`) and automatically rotates the file when it reaches a maximum size (`max_bytes`), keeping a defined number of backups (`backup_count`).

## 3. Data Structure

-   This module does not deal with complex data structures but rather with the configuration of the `logging.Logger` object and its components (`Formatter`, `Filter`, `Handler`).

## 4. Dependencies and Integrations

-   **`robbot.config.settings`:** Used to get settings, although the module prioritizes environment variables for logging configuration.
-   **Integration with Uvicorn:** The code explicitly captures Uvicorn's loggers (`uvicorn`, `uvicorn.error`, `uvicorn.access`), removes their default handlers, and configures them to propagate messages to the root logger. This ensures that web server logs follow the same structured format as the application.
-   **Entire Application:** Any Python file that runs `import logging` and `logger = logging.getLogger(__name__)` will start using the configuration defined here as soon as `configure_logging()` is called at application startup.

## 5. Use Cases

-   **Application Initialization:** The application's `main.py` calls `configure_logging()` at the very beginning to ensure all subsequent events are logged correctly.
-   **Debugging in Development:** A developer is running the application locally. With `LOG_LEVEL=DEBUG` and `LOG_COLOR=true`, they see detailed and colored logs in the console that help them trace the flow of a request through different services.
-   **Error Analysis in Production:** An error occurred in production. The system administrator accesses the `logs/robbot.log` file on the server. They can filter the logs by process ID to isolate the actions of a specific worker and find the cause of the error, thanks to the structured format and precise timestamps.
-   **Monitoring Multiple Workers:** When viewing aggregated logs from multiple worker containers, the distinction by `service_name` ("worker") and `ProcessID` allows understanding which worker processed which job and identifying if a specific worker is having problems.

## 6. Security

-   A good logging system is a security tool. It creates an audit trail that can be used to investigate suspicious activity. However, it is crucial to ensure that sensitive information (passwords, tokens, patient data) is not logged in clear text. The application must have mechanisms to filter or mask this data before passing it to the logger.

## 7. Performance

-   File logging has a small I/O overhead. In very high-performance applications, logging can become a bottleneck. However, for most web applications, the impact is negligible. The `INFO` level setting in production ensures that only essential logs are written, minimizing the impact.

## 8. Testability

-   Functions that depend on logging can be tested by capturing the log output and asserting that the expected messages were emitted with the correct level and format.

## 9. Error Handling

-   The logging module itself is robust. If the log file cannot be written (e.g., due to lack of permission), the `RotatingFileHandler` will usually fail silently. The console output, however, would continue to work.
-   The code prevents duplicate handler configuration by checking if `root.handlers` already exists, which prevents issues in hot-reloading scenarios.

