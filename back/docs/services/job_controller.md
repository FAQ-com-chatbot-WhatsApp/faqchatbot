---
title: "Jobs Controller (job_controller.py)"
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Jobs Controller (`job_controller.py`)

## 1. Description
The Jobs Controller provides an API endpoint for manually triggering background jobs. This is primarily used by administrators for maintenance, testing, or forcing the execution of scheduled tasks outside of their regular schedule. The initial implementation includes an endpoint to run the re-engagement job.

## 2. Architecture and Design
This is a simple controller designed for administrative actions.
- **Security:** Access is strictly limited to users with the 'ADMIN' role.
- **Synchronous Execution:** The controller directly invokes the job function (`run_reengagement_job`) and waits for its completion. For longer-running jobs, this could be refactored to enqueue the job in a background worker queue (like RQ) and return a job ID for status tracking.
- **Direct Function Call:** It calls the job logic directly, which is defined in the `robbot.infra.jobs` module.

## 3. Data Structure
This controller does not define any complex Pydantic schemas, as the primary endpoint (`/jobs/reengagement`) does not require a request body and returns a simple JSON message.

## 4. Dependencies and Integrations
- **`fastapi.APIRouter`**: To define the `/jobs/reengagement` route.
- **`robbot.core.security.get_current_user`**: A dependency to ensure requests are authenticated and to check the user's role.
- **`robbot.infra.jobs.reengagement_job.run_reengagement_job`**: The actual function that contains the business logic for the re-engagement job.
- **`sqlalchemy.orm.Session`**: Injected via `get_db` to be passed down to the job function if it needs database access.

## 5. Use Cases
- **Use Case 1: Manual Re-engagement Campaign:** An administrator wants to run a re-engagement campaign immediately instead of waiting for the scheduled time. They use an internal admin tool that calls `POST /jobs/reengagement`. The controller triggers the job, which finds all eligible inactive conversations and sends them a follow-up message.

## 6. Security
- **Admin-Only Access:** The endpoint is strictly protected. It uses the `get_current_user` dependency and immediately checks if `current_user.role` is `Role.ADMIN`. If not, it raises an `HTTPException` with a `403 Forbidden` status, preventing any unauthorized access.

## 7. Performance
- The performance of this endpoint is directly tied to the performance of the `run_reengagement_job` function itself. Since it runs synchronously, a long-running job could block the server process and cause the request to time out. For jobs that are expected to take more than a few seconds, it is best practice to delegate them to a background worker.

## 8. Testability
- **Unit Testing:** The controller can be tested by mocking the `get_current_user` dependency and the `run_reengagement_job` function. This allows for testing the admin role check and verifying that the job function is called correctly.
- **Integration Testing:** An integration test could call the endpoint and verify that the re-engagement job runs successfully against a test database, checking that the correct conversations are updated.

## 9. Error Handling
- **Permission Denied:** Raises a `403 Forbidden` error if a non-admin user tries to access the endpoint.
- **Job Failure:** A generic `try...except` block wraps the call to `run_reengagement_job`. If the job fails for any reason, the controller catches the exception and returns an `HTTPException` with a `500 Internal Server Error` status, providing a clear error message without crashing the server.
