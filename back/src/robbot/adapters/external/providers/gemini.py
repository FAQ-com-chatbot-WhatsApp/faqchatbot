"""Google Gemini LLM provider implementation.

Implements the LLMProvider interface for Google's Gemini models
using LangChain's ChatGoogleGenerativeAI with automatic model fallback.
"""

import logging

from langchain_google_genai import ChatGoogleGenerativeAI

from robbot.adapters.external.providers.base import LLMProvider
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)

# Gemini models ordered by preference (speed, quota availability, capability)
GEMINI_FALLBACK_MODELS = [
    "gemini-1.5-pro",  # Pro model, most stable
    "gemini-1.5-flash",  # Flash, faster but may have quota issues
    "gemini-pro",  # Legacy, fallback option
]


class GeminiProvider(LLMProvider):
    """Google Gemini LLM provider using LangChain.

    Wraps ChatGoogleGenerativeAI to provide a consistent interface
    for interacting with Google's Gemini models.
    """

    def __init__(
        self,
        api_key: str,
        model: str,
        default_temperature: float = 0.7,
        default_max_tokens: int = 2048,
        timeout: int = 60,
    ):
        """Initialize Gemini provider.

        Args:
            api_key: Google AI API key
            model: Model identifier (e.g., 'gemini-1.5-flash')
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
            self._client = ChatGoogleGenerativeAI(
                model=model,
                google_api_key=api_key,
                temperature=default_temperature,
                max_output_tokens=default_max_tokens,
                timeout=timeout,
            )
            logger.info(
                "Gemini provider initialized: model=%s, temp=%s",
                model,
                default_temperature,
            )
        except Exception as e:
            logger.error("Failed to initialize Gemini provider: %s", e)
            raise LLMError("Gemini", f"Initialization failed: {e}", original_error=e) from e

    def generate_response(
        self,
        prompt: str,
        temperature: float | None = None,
        max_tokens: int | None = None,
    ) -> str:
        """Generate response using Gemini model with automatic fallback.

        Attempts primary model first. If it fails due to quota or unavailability,
        automatically tries alternative models from GEMINI_FALLBACK_MODELS list.

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
        models_to_try = [self._primary_model] + [m for m in GEMINI_FALLBACK_MODELS if m != self._primary_model]
        last_error = None

        for model_name in models_to_try:
            try:
                # Switch model if needed
                if model_name != self._current_model:
                    logger.info("[FALLBACK] Switching to Gemini model: %s", model_name)
                    self._client = ChatGoogleGenerativeAI(
                        model=model_name,
                        google_api_key=self._api_key,
                        temperature=effective_temp,
                        max_output_tokens=effective_max,
                        timeout=self._timeout,
                    )
                    self._current_model = model_name
                else:
                    self._client.temperature = effective_temp
                    self._client.max_output_tokens = effective_max

                response = self._client.invoke(prompt)

                # Log success if using fallback
                if model_name != self._primary_model:
                    logger.info("[SUCCESS] Response generated with fallback model: %s", model_name)

                return response.content

            except Exception as e:
                error_msg = str(e)
                last_error = e

                # Check for quota/rate limit errors (429 RESOURCE_EXHAUSTED)
                if "429" in error_msg and "resource_exhausted" in error_msg:
                    logger.warning(
                        "[QUOTA] Gemini model %s exhausted: %s",
                        model_name,
                        error_msg[:200],
                    )
                    continue  # Try next model

                # Check for model availability errors
                if any(
                    keyword in error_msg
                    for keyword in ["404", "not found", "not_supported", "not supported", "model is not found"]
                ):
                    logger.warning(
                        "[QUOTA] Gemini model %s unavailable: %s",
                        model_name,
                        error_msg[:200],
                    )
                    continue  # Try next model

                # For other errors, fail immediately
                logger.error("Gemini generation failed: %s", e)
                raise LLMError("Gemini", error_msg, original_error=e) from e

        # All models failed
        logger.error("[QUOTA] All Gemini models exhausted. Models tried: %s", models_to_try)
        raise LLMError(
            "Gemini",
            f"All models hit quota limits. Tried: {', '.join(models_to_try)}",
            original_error=last_error,
        ) from last_error

    def is_available(self) -> bool:
        """Check if Gemini provider is available.

        Returns:
            True if client is initialized, False otherwise
        """
        return self._client is not None

    def get_provider_name(self) -> str:
        """Get provider name.

        Returns:
            'gemini'
        """
        return "gemini"

    def get_model_name(self) -> str:
        """Get the currently active model identifier.

        Returns:
            Current model name (may differ from primary if fallback occurred)
        """
        return self._current_model
