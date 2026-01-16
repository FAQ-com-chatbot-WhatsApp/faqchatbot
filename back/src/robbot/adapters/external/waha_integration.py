"""
WAHA WhatsApp implementation of WAHAClientInterface.

Wraps WAHA client for dependency injection.

Resolves Issue #3: Missing Abstraction for External Services
"""

import logging
from typing import Any

from robbot.adapters.external.waha_client import WAHAClient
from robbot.core.interfaces import WAHAClientInterface

logger = logging.getLogger("robbot.adapters.external.waha_integration")


class WAHAIntegration(WAHAClientInterface):
    """
    Concrete implementation of WAHAClientInterface using WAHA.

    Wraps WAHA HTTP API client for dependency injection.
    Enables easy swapping with official WhatsApp API, Twilio, etc.
    """

    def __init__(
        self,
        base_url: str,
        api_token: str,
    ):
        """
        Initialize WAHA integration.

        Args:
            base_url: Base URL for WAHA server
            api_token: API authentication token
        """
        self.base_url = base_url
        self.api_token = api_token

        # Initialize underlying WAHA client
        self._client = WAHAClient(
            base_url=base_url,
            api_key=api_token,
        )

        logger.info(f"Initialized WAHAIntegration with base_url: {base_url}")

    async def send_message(
        self,
        chat_id: str,
        message: str,
        reply_to: str | None = None,
    ) -> dict[str, Any]:
        """
        Send text message via WhatsApp.

        Args:
            chat_id: WhatsApp chat ID (phone number)
            message: Message text
            reply_to: Message ID to reply to (thread)

        Returns:
            Response with message ID and status
        """
        try:
            response = await self._client.send_message(
                chat_id=chat_id,
                message=message,
                reply_to=reply_to,
            )
            logger.debug(f"Sent message to {chat_id}")
            return response
        except Exception as e:
            logger.error(f"Error sending message to {chat_id}: {e}")
            raise

    async def send_media(
        self,
        chat_id: str,
        media_url: str,
        media_type: str,
        caption: str | None = None,
    ) -> dict[str, Any]:
        """
        Send media file via WhatsApp.

        Args:
            chat_id: WhatsApp chat ID
            media_url: URL of media file
            media_type: Type (image, video, audio, document)
            caption: Optional caption text

        Returns:
            Response with message ID and status
        """
        try:
            response = await self._client.send_media(
                chat_id=chat_id,
                media_url=media_url,
                media_type=media_type,
                caption=caption,
            )
            logger.debug(f"Sent {media_type} to {chat_id}")
            return response
        except Exception as e:
            logger.error(f"Error sending media to {chat_id}: {e}")
            raise

    async def close(self) -> None:
        """Clean up resources."""
        try:
            if self._client:
                await self._client.close()
            logger.info("WAHAIntegration closed")
        except Exception as e:
            logger.error(f"Error closing WAHA client: {e}")
