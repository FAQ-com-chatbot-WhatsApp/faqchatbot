"""
Jobs module for async task processing.

Available jobs:
- BaseJob: Base class for all jobs
- MessageProcessingJob, MessageBatchProcessingJob: Process WhatsApp messages
- EscalationJob, MultipleEscalationJob: Transfer conversations to humans
- GeminiAIProcessingJob, MessageAnalysisJob: LLM inference tasks
- ReEngagementJob: Re-engage inactive leads
- ScheduledJob, ReminderJob, CleanupJob, SyncJob: Scheduled tasks
"""

from robbot.infra.jobs.base_job import BaseJob
from robbot.infra.jobs.escalation_job import EscalationJob, MultipleEscalationJob
from robbot.infra.jobs.gemini_job import GeminiAIProcessingJob, MessageAnalysisJob
from robbot.infra.jobs.message_job import MessageBatchProcessingJob, MessageProcessingJob
from robbot.infra.jobs.reengagement_job import ReEngagementJob
from robbot.infra.jobs.scheduler_job import CleanupJob, ReminderJob, ScheduledJob, SyncJob

__all__ = [
    "BaseJob",
    "CleanupJob",
    "EscalationJob",
    "GeminiAIProcessingJob",
    "MessageAnalysisJob",
    "MessageBatchProcessingJob",
    "MessageProcessingJob",
    "MultipleEscalationJob",
    "ReEngagementJob",
    "ReminderJob",
    "ScheduledJob",
    "SyncJob",
]
