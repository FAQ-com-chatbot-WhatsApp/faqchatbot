---
title: "Worker Routes (worker_routes.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Worker Routes (`worker_routes.py`)

## 1. Description
This module defines API endpoints specifically for managing and monitoring the background worker infrastructure. It provides functionalities for retrieving worker analytics, configuring autoscaling, and manually triggering scaling events. These endpoints are intended for administrative use and are critical for maintaining the health and performance of the system's asynchronous processing capabilities.

## 2. Architecture and Design
This controller is designed as an administrative interface for the worker system.
- **Development vs. Production:** The manual scaling endpoints (`/scale`, `/autoscale/trigger`) are explicitly designed for a `docker-compose` environment and are not suitable for production. The code and documentation correctly note that a proper orchestration system like Kubernetes or Docker Swarm should be used in production. This demonstrates an awareness of different deployment environments.
- **Service Delegation:** All the logic for gathering analytics and making scaling decisions is encapsulated in the `WorkerAnalyticsService`.
- **Direct Subprocess Execution:** The scaling endpoints use Python's `subprocess` module to execute `docker compose` commands. This is a direct and simple approach for a development environment but highlights the tight coupling to the underlying containerization technology.

## 3. Data Structure
- **`WorkerAnalytics`**: A Pydantic schema that structures the response for the analytics endpoint, including details about worker counts, queue lengths, and job statuses.
- **`AutoscalingConfig`**: A schema for getting and setting the autoscaling configuration parameters (e.g., thresholds for scaling up or down).
- **`ScaleWorkersRequest`**: The simple request body for the manual scaling endpoint, containing the `target_workers` count.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the routes under the `/workers` prefix.
- **`robbot.services.worker_analytics_service.WorkerAnalyticsService`**: The service that provides all the logic for worker monitoring and scaling decisions.
- **`robbot.core.security.get_current_user`**: Ensures all endpoints are authenticated.
- **`subprocess`**: The Python standard library module used to execute external shell commands (`docker compose`).

## 5. Use Cases
- **Use Case 1: Monitoring Worker Health:** An administrator visits the system health dashboard, which calls `GET /workers/analytics`. The controller returns a snapshot of how many workers are active, how many jobs are in the queue, and how many have failed.
- **Use Case 2: Manual Scaling for a Traffic Spike:** An administrator anticipates a marketing campaign will generate a huge influx of messages. They proactively call `POST /workers/scale` with `{"target_workers": 10}` to increase the number of worker instances to handle the expected load.
- **Use Case 3: Triggering an Autoscale Check:** After a period of high load, an administrator wants to force the system to scale down to save resources. They call `POST /workers/autoscale/trigger`. The `WorkerAnalyticsService` checks the current queue length, determines that the load has decreased, and executes a `docker compose` command to reduce the number of worker instances.

## 6. Security
- **Authentication:** All endpoints are protected by the `get_current_user` dependency, preventing any unauthenticated access to the worker management functions.
- **Command Injection Risk:** The use of `subprocess` to run shell commands can be risky. In this implementation, the command is constructed with a hardcoded string and a validated integer (`request.target_workers`), which mitigates the risk of command injection. However, it's a good example of a feature that requires careful security review.

## 7. Performance
- The performance of the analytics endpoint depends on the efficiency of the `WorkerAnalyticsService`, which queries Redis for queue and worker information. These are typically very fast operations.
- The scaling endpoints' performance depends on the speed of the `docker compose` command, which can take several seconds to complete. The use of a `timeout` in the `subprocess.run` call is a good practice to prevent the request from hanging indefinitely.

## 8. Testability
- Testing the analytics and configuration endpoints is straightforward by mocking the `WorkerAnalyticsService`.
- Testing the scaling endpoints is more complex. It would require mocking the `subprocess.run` function to simulate the success and failure of the `docker compose` command and to verify that the correct command-line arguments are being passed.

## 9. Error Handling
- **Command Failure:** The controller checks the `returncode` of the `subprocess.run` call. If it's non-zero, it raises an exception, which is caught and returned as a `500 Internal Server Error` with the details from `stderr`.
- **Timeout:** If the `docker compose` command takes too long, a `subprocess.TimeoutExpired` exception is caught, and the controller returns a `504 Gateway Timeout` error, which is a more appropriate HTTP status code for this scenario.
- **Generic Errors:** All endpoints are wrapped in a `try...except` block to handle any other unexpected failures gracefully.
