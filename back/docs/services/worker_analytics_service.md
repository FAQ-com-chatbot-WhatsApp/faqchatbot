
# Feature: WorkerAnalyticsService

## 1. Description

The `WorkerAnalyticsService` is a monitoring and analysis service for the queue system workers (RQ - Redis Queue). Its main responsibility is to provide visibility into the health and load of the background processing system, as well as to implement auto-scaling logic to dynamically adjust the number of workers based on demand.

## 2. Architecture and Design

-   **Direct Interaction with Redis and RQ:** The service interacts directly with Redis data structures and the RQ library to obtain real-time information about queues and workers. It does not depend on a database repository, as the data is ephemeral and resides in Redis.
-   **Auto-scaling Logic:** The core of the service is the `_calculate_autoscaling_recommendation` method, which implements the decision logic to scale up (`scale_up`), scale down (`scale_down`), or maintain (`maintain`) the number of workers. The rules are:
    1.  **Minimum Workers:** Ensures that the number of workers never falls below the configured minimum.
    2.  **Scale Up:** Increases the number of workers if the number of pending jobs per worker exceeds a threshold (`scale_up_threshold`).
    3.  **Scale Down:** Reduces the number of workers if all are idle, there are no pending jobs, and the current number is greater than the minimum.
-   **Configuration via Environment Variables:** All auto-scaler parameters (minimum/maximum workers, thresholds) are configurable via environment variables, allowing behavior to be adjusted without changing the code.

## 3. Data Structure

The service produces and consumes dictionaries with monitoring data:
-   **Queue Statistics:** For each queue (`messages`, `ai`, etc.), it reports the number of pending jobs.
-   **Worker Statistics:** Lists all active workers, reporting their state (`idle`, `busy`), the queues they are listening to, and the job they are currently processing.
-   **Auto-scaling Recommendation:** A dictionary with the recommended action (`action`), the target number of workers (`target_workers`), and the reason (`reason`).

## 4. Dependencies and Integrations

-   **`Redis`:** A fundamental dependency. The service connects to Redis to obtain all information.
-   **`rq` (Redis Queue):** The RQ library is used to inspect the state of queues and workers.
-   **Orchestration Scripts (e.g., `autoscale_workers.py`):** This service is designed to be consumed by an external script (likely run periodically via cron or a loop) that reads the recommendation from `should_autoscale` and executes the necessary commands to start or stop worker containers (e.g., `docker-compose up --scale worker=N`).

## 5. Use Cases

-   **Real-Time Monitoring:** An administrator accesses a monitoring dashboard that consumes the `get_analytics` method to display real-time charts on the number of jobs in the queue, active vs. idle workers, and processing success rate.
-   **Automatic Scaling during Peak Usage:** During a spike in patient messages, the number of jobs in the `messages` queue increases rapidly. The `WorkerAnalyticsService` detects that the load per worker has exceeded the threshold and recommends a `scale_up`. The auto-scaling script executes the command to add another worker, and the queue begins to be processed more quickly.
-   **Cost Reduction during Low Demand:** During the early morning, there are no new messages. All jobs are processed, and the workers become idle. The service detects this idleness and recommends a `scale_down`, reducing the number of workers to the minimum and saving computational resources.

## 6. Security

-   The service interacts with Redis, so access to Redis should be password-protected. As it can be exposed via an API for a dashboard, the endpoint that consumes it must be protected and accessible only by administrators.

## 7. Performance

-   Read operations in Redis are extremely fast. The `get_analytics` method is lightweight and can be called frequently (e.g., every 10-30 seconds) for near real-time monitoring.

## 8. Testability

-   To test the service, a mocked Redis client (like `fakeredis`) is necessary. It is possible to populate the fake Redis with queues and workers in different states and then call the service's methods to verify that the statistics and auto-scaling recommendations are calculated correctly according to the defined rules.

## 9. Error Handling

-   The service assumes that the connection to Redis is available. If Redis goes down, calls to the service's methods will fail. A higher layer (like the API endpoint) should handle this exception to prevent the monitoring system from breaking.
-   The calculation of the success rate (`success_rate`) prevents division by zero if no jobs have been processed yet.
