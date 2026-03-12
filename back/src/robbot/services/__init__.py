"""Services module - Main exports.

Note: Imports are done directly from modules to avoid circular dependencies.
Do not import from submodule __init__.py files.
"""

# Bot services - import directly from modules
# Auth services
from robbot.services.auth.auth_services import AuthService
from robbot.services.auth.user_service import UserService
from robbot.services.bot.conversation_orchestrator import ConversationOrchestrator
from robbot.services.bot.conversation_pipeline import ConversationPipeline
from robbot.services.bot.conversation_service import ConversationService
from robbot.services.bot.response_dispatcher import ResponseDispatcher
from robbot.services.bot.response_generator import ResponseGenerator

# Content services
from robbot.services.content.content_service import ContentService
from robbot.services.infrastructure.health_service import HealthService

# Communication services - import directly to avoid circular imports
# from robbot.services.communication.waha_service import WAHAService
# from robbot.services.communication.notification_service import NotificationService
# Infrastructure services
from robbot.services.infrastructure.queue_service import QueueService

# Lead services
from robbot.services.leads.lead_service import LeadService

__all__ = [
    # Bot
    "ConversationOrchestrator",
    "ConversationPipeline",
    "ConversationService",
    "ResponseDispatcher",
    "ResponseGenerator",
    # Leads
    "LeadService",
    # Auth
    "AuthService",
    "UserService",
    # Communication - commented out to avoid circular imports
    # "WAHAService",
    # "NotificationService",
    # Infrastructure
    "QueueService",
    "HealthService",
    # Content
    "ContentService",
]
