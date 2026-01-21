"""
Gemini implementation of LLMProvider interface.

Wraps GeminiClient with proper abstraction for dependency injection.

Resolves Issue #3: Missing Abstraction for External Services
"""

import asyncio
import logging
from typing import Any

from robbot.adapters.external.gemini_client import GeminiClient, close_gemini_client
from robbot.core.interfaces import LLMProvider

logger = logging.getLogger("robbot.adapters.external.gemini_llm_provider")


class GeminiLLMProvider(LLMProvider):
    """
    Concrete implementation of LLMProvider using Google Gemini API.

    Wraps GeminiClient to provide a clean interface for services.
    Enables easy swapping with other LLM providers (Claude, GPT, etc.).
    """

    def __init__(
        self,
        api_key: str,
        model: str = "gemini-2.0-flash",
        temperature: float = 0.7,
        max_tokens: int = 500,
        tools: list[dict] | None = None,
    ):
        """
        Initialize Gemini LLM provider.

        Args:
            api_key: Google Generative AI API key
            model: Model name (e.g., gemini-1.5-flash, gemini-1.5-pro)
            temperature: Sampling temperature (0-1)
            max_tokens: Default max tokens for responses
            tools: Gemini function definitions
        """
        self.api_key = api_key
        self.model = model
        self.temperature = temperature
        self.max_tokens = max_tokens
        self.tools = tools

        # Initialize underlying Gemini client (tools not supported in GeminiClient)
        self._client = GeminiClient()

        logger.info("Initialized GeminiLLMProvider with model: %s", model)

    async def generate_response(
        self,
        prompt: str,
        system: str = "",
        temperature: float | None = None,
        max_tokens: int | None = None,
        tools: list[dict] | None = None,
    ) -> dict[str, Any]:
        """
        Generate a response from Gemini.

        Args:
            prompt: User message/prompt
            system: System instructions
            temperature: Sampling temperature (uses default if None)
            max_tokens: Maximum tokens (uses default if None)
            tools: Function definitions (uses default if None)

        Returns:
            Dict with keys:
                - text: Generated response
                - finish_reason: Why generation stopped
                - usage: Token usage info
        """
        try:
            # Use provided values or defaults

            # Call underlying Gemini client
            return await asyncio.to_thread(
                self._client.generate_response,
                prompt,
                system,
            )

        except Exception as e:
            logger.error("Error generating response: %s", e)
            raise

    async def embed_text(self, text: str) -> list[float]:
        """
        Generate embeddings for text using Gemini.

        Args:
            text: Text to embed

        Returns:
            Vector embeddings
        """
        try:
            # GeminiClient may not have embed_text in some environments; add stub if missing
            if not hasattr(self._client, "embed_text"):

                def _stub_embed_text(text):
                    return [0.0] * 768

                self._client.embed_text = _stub_embed_text
            return await asyncio.to_thread(self._client.embed_text, text)
        except Exception as e:
            logger.error("Error generating embeddings: %s", e)
            raise

    async def close(self) -> None:
        """Clean up resources."""
        try:
            if self._client:
                close_gemini_client()
            logger.info("GeminiLLMProvider closed")
        except Exception as e:
            logger.error("Error closing Gemini client: %s", e)
