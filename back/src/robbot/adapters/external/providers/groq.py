"""Groq LLM provider implementation.

Implements the LLMProvider interface for Groq's models
using LangChain's ChatGroq with automatic model fallback.
"""

import logging

from langchain_groq import ChatGroq

from robbot.adapters.external.providers.base import LLMProvider
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)

# Groq models ordered by preference (speed, capability, availability)
GROQ_FALLBACK_MODELS = [
    "llama-3.3-70b-versatile",  # Latest Llama 3.3, best balance
    "llama-3.1-8b-instant",  # Fast, lower capability (3.1-70b DECOMMISSIONED)
    "mixtral-8x7b-32768",  # Good for long context
    "gemma2-9b-it",  # Google's Gemma, efficient
]


class GroqProvider(LLMProvider):
    """Groq LLM provider using LangChain.

    Wraps ChatGroq to provide a consistent interface for interacting
    with Groq's fast inference models.
    """

    def __init__(
        self,
        api_key: str,
        model: str,
        default_temperature: float = 0.7,
        default_max_tokens: int = 2048,
        timeout: int = 60,
    ):
        """Initialize Groq provider.

        Args:
            api_key: Groq API key
            model: Model identifier (e.g., 'llama-3.3-70b-versatile')
            default_temperature: Default sampling temperature
            default_max_tokens: Default maximum output tokens
            timeout: Request timeout in seconds

        Raises:
            LLMError: If initialization fails
        """
        self._api_key = api_key
        self._primary_model = model
        self._current_model = model
        self._default_temperature = default_temperature
        self._default_max_tokens = default_max_tokens
        self._timeout = timeout

        try:
            self._client = ChatGroq(
                model=model,
                groq_api_key=api_key,
                temperature=default_temperature,
                max_tokens=default_max_tokens,
                timeout=timeout,
            )
            logger.info(
                "Groq provider initialized: model=%s, temp=%s",
                model,
                default_temperature,
            )
        except Exception as e:
            logger.error("Failed to initialize Groq provider: %s", e)
            raise LLMError("Groq", f"Initialization failed: {e}", original_error=e) from e

    def generate_response(
        self,
        prompt: str,
        temperature: float | None = None,
        max_tokens: int | None = None,
    ) -> str:
        """Generate response using Groq model with automatic fallback.

        Attempts primary model first. If it fails due to quota or unavailability,
        automatically tries alternative models from GROQ_FALLBACK_MODELS list.

        Args:
            prompt: Input prompt text
            temperature: Override default temperature
            max_tokens: Override default max tokens

        Returns:
            Generated text response

        Raises:
            LLMError: If all models fail
        """
        effective_temp = temperature if temperature is not None else self._default_temperature
        effective_max = max_tokens if max_tokens is not None else self._default_max_tokens

        # Build list of models to try: primary + fallbacks
        models_to_try = [self._primary_model] + [m for m in GROQ_FALLBACK_MODELS if m != self._primary_model]
        last_error = None

        for model_name in models_to_try:
            try:
                # Switch model if needed
                if model_name != self._current_model:
                    logger.info("[FALLBACK] Switching to Groq model: %s", model_name)
                    self._client = ChatGroq(
                        model=model_name,
                        groq_api_key=self._api_key,
                        temperature=effective_temp,
                        max_tokens=effective_max,
                        timeout=self._timeout,
                    )
                    self._current_model = model_name
                else:
                    self._client.temperature = effective_temp
                    self._client.max_tokens = effective_max

                response = self._client.invoke(prompt)

                # Log success if using fallback
                if model_name != self._primary_model:
                    logger.info("[SUCCESS] Response generated with fallback model: %s", model_name)

                return response.content

            except Exception as e:
                error_msg = str(e).lower()
                last_error = e

                # Check for quota/rate limit or model availability errors
                if any(
                    keyword in error_msg
                    for keyword in [
                        "rate_limit",
                        "quota",
                        "429",
                        "503",
                        "decommissioned",
                        "model not found",
                        "not found",
                        "invalid_request_error",
                    ]
                ):
                    logger.warning(
                        "[QUOTA] Groq model %s hit limit or unavailable: %s",
                        model_name,
                        str(e)[:200],
                    )
                    continue  # Try next model

                # For other errors, fail immediately
                logger.error("Groq generation failed: %s", e)
                raise LLMError("Groq", str(e), original_error=e) from e

        # All models failed
        logger.error("[QUOTA] All Groq models exhausted. Models tried: %s", models_to_try)
        raise LLMError(
            "Groq",
            f"All models hit rate limits. Tried: {', '.join(models_to_try)}",
            original_error=last_error,
        ) from last_error

    def is_available(self) -> bool:
        """Check if Groq provider is available.

        Returns:
            True if client is initialized, False otherwise
        """
        return self._client is not None

    def get_provider_name(self) -> str:
        """Get provider name.

        Returns:
            'groq'
        """
        return "groq"

    def get_model_name(self) -> str:
        """Get the currently active model identifier.

        Returns:
            Current model name (may differ from primary if fallback occurred)
        """
        return self._current_model
