"""
LLM client with multi-provider support and automatic fallback.

This module provides a singleton client to interact with LLM providers (Gemini, Groq)
using a provider abstraction layer. Handles connection setup, prompt formatting,
error handling, and automatic fallback when primary provider fails.
"""

import logging
from typing import Any

from robbot.adapters.external.providers import LLMProviderManager, ProviderType
from robbot.adapters.external.providers.gemini import GeminiProvider
from robbot.adapters.external.providers.groq import GroqProvider
from robbot.config.settings import settings
from robbot.core.custom_exceptions import LLMError
from robbot.core.interfaces import LLMProvider

logger = logging.getLogger(__name__)

_singleton: dict[str, "LLMClient | None"] = {"client": None}


class LLMClient(LLMProvider):
    """
    LLM client with multi-provider support and automatic fallback.

    Uses provider abstraction to support multiple LLM services:
    - Gemini (Google)
    - Groq (Llama models)

    Responsibilities:
    - Initialize and manage LLM providers
    - Generate responses with automatic fallback
    - Provide a singleton interface for the application
    """

    def __init__(self):
        """
        Initialize LLMClient with provider manager and available providers.

        Raises:
            LLMError: If initialization fails
        """
        self.manager = None
        self._load_config()

    def _load_config(self):
        """Load configuration from DB with fallback to settings.py (Env)."""
        from robbot.infra.db.session import get_sync_session
        from robbot.infra.persistence.repositories.system_setting_repository import SystemSettingRepository

        try:
            with get_sync_session() as db:
                repo = SystemSettingRepository(db)
                db_settings = repo.get_all_settings()

                # Dynamic settings
                google_api_key = db_settings.get("GOOGLE_API_KEY", settings.GOOGLE_API_KEY)
                gemini_model = db_settings.get("GEMINI_MODEL", settings.GEMINI_MODEL)
                gemini_max_tokens = int(db_settings.get("GEMINI_MAX_TOKENS", settings.GEMINI_MAX_TOKENS))
                gemini_temperature = float(db_settings.get("GEMINI_TEMPERATURE", settings.GEMINI_TEMPERATURE))

                groq_api_key = db_settings.get("GROQ_API_KEY", settings.GROQ_API_KEY)
                groq_model = db_settings.get("GROQ_MODEL", settings.GROQ_MODEL)
                groq_max_tokens = int(db_settings.get("GROQ_MAX_TOKENS", settings.GROQ_MAX_TOKENS))
                groq_temperature = float(db_settings.get("GROQ_TEMPERATURE", settings.GROQ_TEMPERATURE))

                primary_provider = db_settings.get("LLM_PRIMARY_PROVIDER", settings.LLM_PRIMARY_PROVIDER)
                enable_fallback = (
                    db_settings.get("LLM_ENABLE_FALLBACK", str(settings.LLM_ENABLE_FALLBACK)).lower() == "true"
                )

                self._initialize_manager(
                    primary=primary_provider,
                    enable_fallback=enable_fallback,
                    google_key=google_api_key,
                    gemini_model=gemini_model,
                    gemini_max_tokens=gemini_max_tokens,
                    gemini_temp=gemini_temperature,
                    groq_key=groq_api_key,
                    groq_model=groq_model,
                    groq_max_tokens=groq_max_tokens,
                    groq_temp=groq_temperature,
                )
        except Exception as e:
            logger.error("[ERROR] Failed to load LLM configuration from DB, using defaults: %s", e)
            self._initialize_manager(
                primary=settings.LLM_PRIMARY_PROVIDER,
                enable_fallback=settings.LLM_ENABLE_FALLBACK,
                google_key=settings.GOOGLE_API_KEY,
                gemini_model=settings.GEMINI_MODEL,
                gemini_max_tokens=settings.GEMINI_MAX_TOKENS,
                gemini_temp=settings.GEMINI_TEMPERATURE,
                groq_key=settings.GROQ_API_KEY,
                groq_model=settings.GROQ_MODEL,
                groq_max_tokens=settings.GROQ_MAX_TOKENS,
                groq_temp=settings.GROQ_TEMPERATURE,
            )

    def _initialize_manager(
        self,
        primary,
        enable_fallback,
        google_key,
        gemini_model,
        gemini_max_tokens,
        gemini_temp,
        groq_key,
        groq_model,
        groq_max_tokens,
        groq_temp,
    ):
        """Initialize and register providers."""
        try:
            # Determine primary provider
            primary_type: ProviderType = "gemini" if primary == "gemini" else "groq"

            # Initialize provider manager
            self.manager = LLMProviderManager(
                primary_provider=primary_type,
                enable_fallback=enable_fallback,
            )

            # Register Gemini provider
            if google_key:
                gemini = GeminiProvider(
                    api_key=google_key,
                    model=gemini_model,
                    default_temperature=gemini_temp,
                    default_max_tokens=gemini_max_tokens,
                    timeout=settings.LLM_TIMEOUT,
                )
                self.manager.register_provider("gemini", gemini)
                logger.info("[PROVIDER] Gemini registered (model=%s)", gemini_model)

            # Register Groq provider
            if groq_key:
                groq = GroqProvider(
                    api_key=groq_key,
                    model=groq_model,
                    default_temperature=groq_temp,
                    default_max_tokens=groq_max_tokens,
                    timeout=settings.LLM_TIMEOUT,
                )
                self.manager.register_provider("groq", groq)
                logger.info("[PROVIDER] Groq registered (model=%s)", groq_model)

            active_info = self.manager.get_active_provider_info()
            logger.info("[SUCCESS] LLMClient (re)initialized with %s", active_info["provider"])

        except Exception as e:
            logger.error("[ERROR] Failed to initialize LLM providers: %s", e)
            raise LLMError("LLMClient", f"Provider initialization failed: {e}") from e

    async def generate_response(
        self,
        prompt: str,
        context: str | None = None,
        max_retries: int = 3,
    ) -> dict[str, Any]:
        """Generate a response from LLM with automatic provider fallback."""
        try:
            logger.info("[INFO] Generating LLM response via provider manager")

            # Provider manager handles fallback automatically
            return await self.manager.generate_response(
                prompt=prompt,
                context=context,
                max_retries=max_retries,
            )

        except LLMError:
            raise
        except Exception as e:
            logger.error("[ERROR] Unexpected error generating response: %s", e, exc_info=True)
            raise LLMError("LLMClient", f"Unexpected error: {e}", original_error=e) from e

    async def generate_structured(
        self,
        prompt: str,
        schema: dict[str, Any],
        context: str | None = None,
    ) -> dict[str, Any]:
        """Generate structured response via active provider."""
        provider = self.manager._select_provider()
        return await provider.generate_structured(prompt, schema, context)

    async def call_function(
        self,
        prompt: str,
        tools: list[dict[str, Any]],
        context: str | None = None,
    ) -> dict[str, Any]:
        """Call function via active provider."""
        provider = self.manager._select_provider()
        return await provider.call_function(prompt, tools, context)

    async def embed_text(self, text: str) -> list[float]:
        """Generate embeddings via active provider."""
        provider = self.manager._select_provider()
        return await provider.embed_text(text)

    async def close(self) -> None:
        """Cleanup resources."""
        if self.manager:
            await self.manager.close()

    def refresh(self) -> None:
        """Reload configuration and recreate providers."""
        logger.info("[CONFIG] Refreshing LLM configuration...")
        self._load_config()


def get_llm_client() -> LLMClient:
    """Get singleton instance of LLMClient."""
    client = _singleton.get("client")
    if client is None:
        _singleton["client"] = LLMClient()
        logger.info("LLMClient initialized as singleton")
    return _singleton["client"]  # type: ignore[return-value]


def close_llm_client() -> None:
    """Close LLMClient singleton instance."""
    if _singleton.get("client") is not None:
        logger.info("Closing LLMClient singleton")
    _singleton["client"] = None
