"""LLM Provider Manager with fallback strategy.

Manages multiple LLM providers and automatically selects the best available
provider based on configuration and availability. Implements Strategy Pattern.
"""

import logging
from typing import Literal

from robbot.adapters.external.providers.base import LLMProvider
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)

ProviderType = Literal["gemini", "groq"]


class LLMProviderManager:
    """Manages LLM providers with automatic fallback.

    Implements Strategy Pattern to allow runtime selection of LLM providers.
    Supports fallback to secondary provider if primary fails.
    """

    def __init__(
        self,
        primary_provider: ProviderType,
        enable_fallback: bool = True,
    ):
        """Initialize provider manager.

        Args:
            primary_provider: Primary provider to use ('gemini' or 'groq')
            enable_fallback: Enable fallback to other providers if primary fails
        """
        self._primary_provider_type = primary_provider
        self._enable_fallback = enable_fallback
        self._providers: dict[ProviderType, LLMProvider | None] = {
            "gemini": None,
            "groq": None,
        }
        self._current_provider: LLMProvider | None = None

    def register_provider(self, provider_type: ProviderType, provider: LLMProvider) -> None:
        """Register a provider instance.

        Args:
            provider_type: Type of provider ('gemini' or 'groq')
            provider: Initialized provider instance
        """
        self._providers[provider_type] = provider
        logger.info("Registered %s provider: %s", provider_type, provider.get_model_name())

    def _select_provider(self) -> LLMProvider:
        """Select best available provider.

        Returns:
            Available provider instance

        Raises:
            LLMError: If no providers are available
        """
        primary = self._providers.get(self._primary_provider_type)
        if primary and primary.is_available():
            return primary

        if self._enable_fallback:
            for provider_type, provider in self._providers.items():
                if provider_type != self._primary_provider_type and provider and provider.is_available():
                    logger.warning(
                        "Primary provider %s unavailable, falling back to %s",
                        self._primary_provider_type,
                        provider_type,
                    )
                    return provider

        raise LLMError(
            "ProviderManager",
            f"No available providers. Primary: {self._primary_provider_type}, Fallback: {self._enable_fallback}",
        )

    def generate_response(
        self,
        prompt: str,
        temperature: float | None = None,
        max_tokens: int | None = None,
    ) -> str:
        """Generate response using best available provider.

        Automatically selects provider and falls back if configured.

        Args:
            prompt: Input prompt text
            temperature: Sampling temperature override
            max_tokens: Max tokens override

        Returns:
            Generated text response

        Raises:
            LLMError: If all providers fail
        """
        provider = self._select_provider()

        try:
            response = provider.generate_response(prompt, temperature, max_tokens)
            logger.info(
                "Response generated: provider=%s, model=%s, length=%d",
                provider.get_provider_name(),
                provider.get_model_name(),
                len(response),
            )
            return response

        except LLMError as e:
            if self._enable_fallback and provider.get_provider_name() == self._primary_provider_type:
                logger.warning("Primary provider failed, attempting fallback: %s", e)

                for provider_type, fallback_provider in self._providers.items():
                    if provider_type != self._primary_provider_type and fallback_provider:
                        try:
                            response = fallback_provider.generate_response(prompt, temperature, max_tokens)
                            logger.info(
                                "Fallback successful: provider=%s, model=%s",
                                fallback_provider.get_provider_name(),
                                fallback_provider.get_model_name(),
                            )
                            return response
                        except LLMError as fallback_error:
                            logger.error("Fallback provider failed: %s", fallback_error)
                            continue

            raise

    def get_active_provider_info(self) -> dict[str, str]:
        """Get information about current active provider.

        Returns:
            Dictionary with provider name and model
        """
        try:
            provider = self._select_provider()
            return {
                "provider": provider.get_provider_name(),
                "model": provider.get_model_name(),
            }
        except LLMError:
            return {
                "provider": "none",
                "model": "unavailable",
            }
