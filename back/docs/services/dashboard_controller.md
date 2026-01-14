---
title: "Dashboard & Metrics Controller (dashboard_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Dashboard & Metrics Controller (`dashboard_controller.py`)

## 1. Description
This controller is the powerhouse for the analytics dashboard. It provides a rich set of endpoints for querying performance metrics, conversion statistics, and real-time data. It includes advanced features like WebSocket streaming for live updates and data export to PDF and Excel formats. The controller is heavily optimized with caching and follows strict security protocols.

## 2. Architecture and Design
The controller is designed to be a comprehensive, read-only interface for all analytics data.
- **Service-Oriented:** It delegates all complex data aggregation and calculation logic to the `MetricsService`.
- **Dependency Injection:** It heavily uses FastAPI's DI system to inject the `MetricsService`, database sessions, and the current user, promoting testability and separation of concerns.
- **Date Parsing and Validation:** A utility function `parse_dates` centralizes the logic for handling date ranges, supporting both absolute dates and relative periods (e.g., "30d"), and includes critical validation to ensure `start_date` is not after `end_date`.
- **WebSocket for Real-Time:** It implements a secure WebSocket endpoint (`/ws/realtime`) for pushing live dashboard data to clients, demonstrating advanced FastAPI capabilities.
- **Data Export:** It provides endpoints to export reports in different formats (PDF, Excel) by leveraging an `ExportService`.

## 3. Data Structure
The controller uses a wide array of Pydantic schemas defined in `robbot.schemas.metrics_schemas` to structure the complex data returned by its endpoints. Examples include:
- **`DashboardSummaryResponse`**: For high-level KPIs.
- **`ConversionFunnelResponse`**: For multi-stage funnel data.
- **`PerformanceReportSchema`**: A composite schema that aggregates multiple performance metrics into a single report.
- **`RealtimeDashboardSchema`**: The schema for data pushed through the WebSocket.

## 4. Dependencies and Integrations
- **`fastapi`**: For routing, dependency injection, and WebSocket handling.
- **`robbot.services.analytics.metrics_service.MetricsService`**: The core dependency for all data-related logic.
- **`robbot.services.export_service.ExportService`**: Used to generate PDF and Excel files.
- **`robbot.core.security`**: For authentication (`get_current_user`) and JWT validation for WebSockets.
- **`robbot.infra.redis.client`**: The `MetricsService` uses Redis for caching query results, which is configured at the service level.

## 5. Use Cases
- **Use Case 1: Loading the Main Dashboard:** A user opens the dashboard. The frontend calls `GET /metrics/dashboard`, `GET /metrics/conversion-funnel`, and other endpoints to populate the initial view with data from the last 30 days.
- **Use Case 2: Real-Time Monitoring:** An admin opens the live monitoring page. The frontend establishes a WebSocket connection to `ws:///metrics/ws/realtime`, passing the JWT token. The server then pushes updates every 5 seconds, allowing the admin to see active conversations and queue stats change in real time.
- **Use Case 3: Generating a Monthly Report:** A manager needs to present the monthly performance. They use the dashboard's date filter to select the last month and click "Export to PDF". The frontend calls `GET /metrics/performance/report/export/pdf` with the appropriate date range, which triggers the download of a professionally formatted PDF report.

## 6. Security
- **Authentication:** All HTTP endpoints are protected with the `get_current_user` dependency.
- **Admin-Only Endpoints:** Certain sensitive metrics, like bot autonomy rate, are further protected by an `check_admin` dependency, which raises a 403 error for non-admin users.
- **WebSocket Security:** The WebSocket endpoint implements a robust security model:
    1. It requires a JWT token to be passed as a query parameter.
    2. It verifies the token *before* accepting the connection.
    3. It includes rate-limiting to prevent a single user from opening too many connections.

## 7. Performance
- **Caching:** The documentation for each endpoint explicitly mentions its cache duration (e.g., "Cache 15min"). This caching is handled within the `MetricsService`, likely using Redis, to avoid expensive database queries on every request.
- **Asynchronous Operations:** The WebSocket endpoint is fully asynchronous, allowing it to handle many concurrent real-time connections efficiently.
- **Optimized Queries:** The `MetricsService` is responsible for crafting efficient SQL queries to aggregate the analytics data.

## 8. Testability
- The controller's endpoints can be unit-tested by mocking the `MetricsService` and `get_current_user`.
- Testing the WebSocket functionality requires a specialized setup using FastAPI's `TestClient`, which provides a context manager for testing WebSocket communications.
- Integration tests are essential to verify that the data returned by the `MetricsService` is correctly serialized and that the date parsing and security checks work as expected.

## 9. Error Handling
- **Invalid Date Ranges:** The `parse_dates` function raises an `HTTPException` with a `400 Bad Request` status if `start_date > end_date`.
- **Authentication/Authorization Errors:** The security dependencies handle raising `401 Unauthorized` and `403 Forbidden` exceptions.
- **WebSocket Errors:** The WebSocket endpoint has detailed error handling, including closing the connection with specific codes for invalid tokens or too many connections, and handling unexpected disconnects gracefully.
