
# Feature: MetricsService

## 1. Description

The `MetricsService` is the main orchestrator for calculating and presenting business and application performance metrics. It acts as a high-level layer that consumes data from the `AnalyticsRepository` and adds an intelligent caching layer with Redis. The goal is to provide analytical data quickly and efficiently to the frontend, avoiding repetitive and costly database calculations.

## 2. Architecture and Design

-   **Cache-Aside Pattern:** The service implements the *cache-aside* pattern. For each metric request, it first tries to fetch the result from the Redis cache. If the data is not found (cache miss), it executes the calculation function (`compute_fn`), saves the result to Redis with a specific TTL (Time-To-Live), and finally returns it.
-   **Caching Strategy with Multiple TTLs:** The service uses different cache lifetimes, depending on the nature of the data, which optimizes both performance and the timeliness of the information:
    -   **Real-time (30s):** For data that changes rapidly, such as the dashboard summary.
    -   **Metrics (15min):** For business metrics that do not require instant updates.
    -   **History (1h):** For historical data that is immutable.
    -   **Reports (30min):** For aggregated reports that are computationally expensive.
-   **Consistent Cache Key Generation:** It uses a function (`_generate_cache_key`) to create standardized and deterministic cache keys, including the metric name, the period, the user ID (if applicable), and a hash of any other parameters that affect the result. This ensures that the same request always generates the same key.
-   **Dependency Injection:** The service receives the `AnalyticsRepository`, the Redis client, and the `RQQueueManager` as dependencies in its constructor, facilitating testability and inversion of control.

## 3. Data Structure

-   **Input:** The methods receive parameters such as `start_date`, `end_date`, `user_id`, and `granularity` to filter and aggregate the data.
-   **Output:** All methods return well-structured dictionaries, containing a `"period"` section and the requested metric data (e.g., `"kpis"`, `"funnel"`, `"time_stats"`). Serialization to JSON (and deserialization) is handled internally, including the conversion of `datetime` objects.

## 4. Dependencies and Integrations

-   **`AnalyticsRepository`:** The data access layer that executes complex SQL queries to extract raw metrics from the database.
-   **`Redis`:** Used as the cache backend to store computed results.
-   **`RQQueueManager`:** Injected to potentially enqueue background cache recalculation jobs (although the usage is not explicit in the shown methods, the dependency is present).
-   **API Endpoints:** The API controllers (in `api/controllers/`) invoke the `MetricsService` methods to serve data to the frontend.

## 5. Use Cases

-   **Dashboard Loading:** When a user accesses the dashboard, the frontend makes a call to an endpoint that invokes `get_dashboard_summary`. Thanks to the cache, if multiple users access it in a short period, the heavy database calculation will be executed only once.
-   **Performance Report Generation:** An administrator requests a performance report (`get_performance_report`). The `MetricsService` orchestrates calls to several other metric functions (`get_bot_response_time`, `get_handoff_rate`, etc.), aggregates the results, and stores the complete report in the cache for 30 minutes.
-   **Conversion Funnel Analysis:** A marketing manager analyzes the conversion funnel (`get_conversion_funnel`) to identify bottlenecks in the lead qualification process.

## 6. Security

-   The service itself does not implement security logic, but the cache key generation can include a `user_id`, ensuring that data cached for one user does not leak to another if the metric is user-specific (e.g., `get_response_time_stats`).

## 7. Performance

-   **Caching:** Extensive use of caching is the main performance strategy, drastically reducing the load on the database and the API response time for repeated requests.
-   **Cache Invalidation:** The service includes a `_invalidate_cache_pattern` method that allows for selective cache invalidation (e.g., when new relevant data is inserted), although its call is not explicit in the retrieval methods.

## 8. Testability

-   The service is highly testable. In unit tests, the `AnalyticsRepository` and the `Redis` client can be mocked.
    -   The "cache hit" scenario can be tested (verifying that `redis.get` is called and `compute_fn` is not).
    -   The "cache miss" scenario can be tested (verifying that `compute_fn` and `redis.setex` are called).
    -   It can be verified that the cache key is generated correctly based on the parameters.

## 9. Error Handling

-   **Redis Failure:** If Redis is unavailable, the service is resilient. It logs a warning and proceeds to calculate the metric directly from the database, operating in a temporary "no-cache" mode. This ensures service availability even with the failure of its cache dependency.
