"""
Service for filtering WhatsApp messages based on business rules and state (deduplication).
Ensures only valid, new, and allowed messages are processed.
"""
import logging
from typing import Set

from robbot.config.settings import settings
from robbot.infra.redis.client import get_redis_client

logger = logging.getLogger(__name__)


class MessageFilterService:
    """
    Filters inbound messages applying:
    - Self-message checks (fromMe)
    - DEV_MODE sender allow-listing
    - Deduplication (Redis check of message ID)
    """

    def __init__(self):
        self.redis = get_redis_client()

    def should_process(self, message: dict, allowed_senders: Set[str] | None = None) -> bool:
        """
        Determines if a message should be processed.
        
        Args:
            message: Raw message dict from WAHA.
            allowed_senders: Optional set of allowed sender IDs (for DEV mode).

        Returns:
            True if message is valid and new, False otherwise.
        """
        message_id = message.get("id")
        sender = message.get("from")

        # 1. Ignore messages sent by the bot itself
        if message.get("fromMe", True):
            return False

        # 2. DEV Mode Restrictions
        if settings.DEV_MODE and allowed_senders:
            if not sender:
                return False
                
            # Check exact match or phone number match
            sender_base = sender.split("@")[0]
            
            # Using set for O(1) lookup
            if sender not in allowed_senders and sender_base not in allowed_senders:
                # Logging here might be spammy if called in a loop, 
                # but caller should handle loop-level caching or logging suppression.
                return False

        # 3. Deduplication Check (Idempotency)
        if not message_id or self._is_processed(message_id):
            return False

        return True

    def mark_as_processed(self, message_id: str):
        """Marks a message ID as processed in Redis with 24h TTL."""
        if message_id:
            key = f"waha:processed:{message_id}"
            self.redis.set(key, "1", ex=86400)

    def _is_processed(self, message_id: str) -> bool:
        """Checks if message ID exists in Redis."""
        key = f"waha:processed:{message_id}"
        return bool(self.redis.get(key))
