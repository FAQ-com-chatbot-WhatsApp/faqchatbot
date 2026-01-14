---
title: Queue Service
version: 1.0
date_created: 2026-01-13
last_updated: 2026-01-13
---
# Implementation Plan: Queue Service (`queue_service.py`)

## Brief description of the requirements and goals of the feature
This service is the central nervous system for all asynchronous operations in the application. It is responsible for enqueuing, monitoring, and managing background jobs. By offloading time-consuming tasks (like processing AI requests or handling escalations) to background workers, it ensures the main application remains responsive. It uses Redis Queue (RQ) as the underlying queueing system.

## Architecture and design
The `QueueService` provides a high-level abstraction over the `QueueManager`, which handles the direct interaction with RQ.

- **Job Enqueueing**:
    - It provides specific methods for different types of jobs: `enqueue_message_processing`, `enqueue_ai_processing`, and `enqueue_escalation`.
    - Each method creates a corresponding `Job` object (e.g., `MessageProcessingJob`) and places it onto the correct queue (`messages`, `ai`, or `escalation`). This separation ensures that different types of tasks can be processed by dedicated workers.
    - It also supports scheduling jobs to run at a future time with `enqueue_scheduled_job`.

- **Job Monitoring**:
    - `get_job_status`: Allows checking the status of any job by its ID (e.g., `pending`, `started`, `finished`, `failed`).
    - `get_queue_stats`: Provides high-level statistics about all queues, such as the number of jobs waiting.
    - `get_failed_jobs`: Retrieves jobs from the "failed" queue (also known as a Dead Letter Queue or DLQ), which is crucial for debugging and retrying failed tasks.

- **Job Management**:
    - `retry_job` / `retry_all_failed`: Provides a mechanism to re-enqueue failed jobs for another attempt.
    - `clear_failed_queue`: Allows an administrator to permanently delete all jobs from the failed queue.
    - `cancel_job`: Attempts to cancel a job that is currently in the queue but has not yet started.

- **Health Check**:
    - `health_check`: A vital function for system monitoring, it checks the connectivity and status of the underlying Redis and RQ components.

## Tasks
- [x] Enqueue different types of jobs to their respective queues.
- [x] Schedule jobs to be executed at a specific time in the future.
- [x] Monitor the status and progress of individual jobs.
- [x] Provide statistics for all queues.
- [x] Retrieve and manage jobs in the failed queue.
- [x] Implement functionality to retry or cancel jobs.
- [x] Provide a health check endpoint for the queueing system.

## Open questions
1. What is the retry policy for failed jobs? Is it automatic, or does it always require manual intervention via `retry_job`?
2. The `ScheduledJob` defaults to the `escalation` queue. Is this intentional, or should the queue be determined by the job's task type?
3. How are the results of completed jobs handled? The `result_ttl` setting implies they are stored in Redis for a period, but how does the application access them?
