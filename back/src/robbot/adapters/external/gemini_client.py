"""
LLM client with multi-provider support and automatic fallback.

This module provides a singleton client to interact with LLM providers (Gemini, Groq)
using a provider abstraction layer. Handles connection setup, prompt formatting,
error handling, and automatic fallback when primary provider fails.
"""

import logging
import time
from typing import Any

from robbot.adapters.external.providers import LLMProviderManager, ProviderType
from robbot.adapters.external.providers.gemini import GeminiProvider
from robbot.adapters.external.providers.groq import GroqProvider
from robbot.config.settings import settings
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)

_singleton: dict[str, "GeminiClient | None"] = {"client": None}


class GeminiClient:
    """
    LLM client with multi-provider support and automatic fallback.

    Uses provider abstraction to support multiple LLM services:
    - Gemini (Google)
    - Groq (Llama models)

    Responsibilities:
    - Initialize and manage LLM providers
    - Generate responses with automatic fallback
    - Handle errors and log all interactions
    - Provide a singleton interface for the application
    """

    def __init__(self):
        """
        Initialize GeminiClient with provider manager and available providers.

        Raises:
            LLMError: If initialization fails
        """
        try:
            # Determine primary provider from settings
            primary: ProviderType = "gemini" if settings.LLM_PRIMARY_PROVIDER == "gemini" else "groq"

            # Initialize provider manager
            self.manager = LLMProviderManager(
                primary_provider=primary,
                enable_fallback=settings.LLM_ENABLE_FALLBACK,
            )

            # Register Gemini provider
            if settings.GOOGLE_API_KEY:
                gemini = GeminiProvider(
                    api_key=settings.GOOGLE_API_KEY,
                    model=settings.GEMINI_MODEL,
                    default_temperature=settings.GEMINI_TEMPERATURE,
                    default_max_tokens=settings.GEMINI_MAX_TOKENS,
                    timeout=settings.LLM_TIMEOUT,
                )
                self.manager.register_provider("gemini", gemini)
                logger.info("[PROVIDER] Gemini registered (model=%s)", settings.GEMINI_MODEL)

            # Register Groq provider
            if settings.GROQ_API_KEY:
                groq = GroqProvider(
                    api_key=settings.GROQ_API_KEY,
                    model=settings.GROQ_MODEL,
                    default_temperature=settings.GROQ_TEMPERATURE,
                    default_max_tokens=settings.GROQ_MAX_TOKENS,
                    timeout=settings.LLM_TIMEOUT,
                )
                self.manager.register_provider("groq", groq)
                logger.info("[PROVIDER] Groq registered (model=%s)", settings.GROQ_MODEL)

            active_provider = self.manager.get_active_provider_info()
            logger.info(
                "[SUCCESS] GeminiClient initialized with %s as primary provider",
                active_provider["provider"],
            )
        except Exception as e:
            logger.error("[ERROR] Failed to initialize GeminiClient: %s", e)
            raise LLMError("LLMClient", f"Initialization failed: {e}", original_error=e) from e

    def generate_response(
        self,
        prompt: str,
        context: str | None = None,
    ) -> dict[str, Any]:
        """
        Generate a response from LLM with automatic provider fallback.

        Attempts to use primary provider. If it fails, automatically falls back
        to secondary provider (if enabled in settings).

        Args:
            prompt: Main user prompt
            context: Optional context string (conversation history, docs, etc)

        Returns:
            dict: {
                "response": str (LLM output),
                "tokens_used": None (not available),
                "latency_ms": int (response time in ms),
                "model": str (model name used),
                "provider": str (provider name used),
                "finish_reason": None (not available)
            }

        Raises:
            LLMError: If all providers fail or no providers available
        """
        full_prompt = self._build_full_prompt(prompt, context)

        try:
            logger.info("[INFO] Generating LLM response via provider manager")
            start_time = time.time()

            # Provider manager handles fallback automatically
            response_text = self.manager.generate_response(
                prompt=full_prompt,
                temperature=None,  # Use provider defaults
                max_tokens=None,  # Use provider defaults
            )

            latency_ms = int((time.time() - start_time) * 1000)

            # Get active provider info
            provider_info = self.manager.get_active_provider_info()

            logger.info(
                "[SUCCESS] Response generated (provider=%s, model=%s, %sms)",
                provider_info["provider"],
                provider_info["model"],
                latency_ms,
            )

            return {
                "response": response_text,
                "tokens_used": None,
                "latency_ms": latency_ms,
                "model": provider_info["model"],
                "provider": provider_info["provider"],
                "finish_reason": None,
            }

        except LLMError:
            # Re-raise LLMError as-is
            raise
        except Exception as e:
            logger.error("[ERROR] Unexpected error generating response: %s", e, exc_info=True)
            raise LLMError("LLMClient", f"Unexpected error: {e}", original_error=e) from e

    def _build_full_prompt(self, prompt: str, context: str | None) -> str:
        """
        Compose the full prompt, optionally including context.

        Args:
            prompt: Main user prompt
            context: Optional context string

        Returns:
            str: Full prompt with context prepended if provided
        """
        if context:
            return f"Context:\n{context}\n\nPrompt:\n{prompt}"
        return prompt


def get_gemini_client() -> GeminiClient:
    """
    Get singleton instance of GeminiClient.

    Returns:
        GeminiClient: Singleton instance
    """
    client = _singleton.get("client")
    if client is None:
        _singleton["client"] = GeminiClient()
        logger.info("GeminiClient initialized as singleton")
    return _singleton["client"]  # type: ignore[return-value]


def close_gemini_client() -> None:
    """
    Close GeminiClient singleton instance (cleanup).
    """
    if _singleton.get("client") is not None:
        logger.info("Closing GeminiClient singleton")
    _singleton["client"] = None
