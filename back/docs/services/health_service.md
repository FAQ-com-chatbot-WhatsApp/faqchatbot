---
title: Health Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Health Service (`health_service.py`)

## Brief description of the requirements and goals of the feature
This service provides a critical function for system monitoring: a health check endpoint. Its purpose is to verify the status and connectivity of all the application's essential downstream services and dependencies. This allows for automated monitoring, quick diagnosis of problems, and can be used by orchestration tools (like Kubernetes or Railway) to determine if the application is running correctly.

## Architecture and design
The `HealthService` is designed to be a comprehensive, single point of status information. Its main method, `get_health`, performs a series of checks on different components:

- **Database (`HealthRepository.ping`)**: It performs a simple, lightweight query (like `SELECT 1`) on the database to ensure that the connection is alive and responsive.

- **Redis (`HealthRepository.check_redis_connection`)**: It sends a `PING` command to the Redis server to verify connectivity. Redis is crucial for caching, session management, and background job queueing.

- **WAHA (WhatsApp HTTP API)**: It calls the `ping` endpoint of the external `WAHAClient`. This is vital because if the connection to the WhatsApp gateway is down, the bot cannot send or receive messages.

- **Queue System (`QueueService.health_check`)**: It calls the health check method of the `QueueService`, which in turn verifies the status of the underlying Redis Queue (RQ) system. This ensures that background jobs can be processed.

- **Active Sessions**: It performs a count of active WhatsApp sessions to provide an operational metric.

The service aggregates the results of all these checks into a single `HealthOut` object. If any of the critical components fail their check, the overall status is marked as `"unhealthy"`.

## Tasks
- [x] Check the health of the primary database connection.
- [x] Check the health of the Redis connection.
- [x] Check the health of the connection to the WAHA gateway.
- [x] Check the health of the background job queueing system.
- [x] Count the number of active WhatsApp sessions.
- [x] Aggregate all component statuses into a single, clear health report.
- [ ] Persist an alert if a critical failure is detected (mentioned in docstring but not implemented).

## Open questions
1. The docstring mentions persisting an alert on critical failure. Is this feature planned, and which system would it alert (e.g., PagerDuty, Slack, email)?
2. The health check for WAHA and the queues involves getting the client/service instances via a global getter (`get_waha_client`, `get_queue_service`). How does this pattern work with dependency injection and testing?
3. If a component is "unhealthy," does the health check provide any more detailed diagnostic information beyond the exception string?
