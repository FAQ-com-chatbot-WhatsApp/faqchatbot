"""
Gemini AI client for Google Generative AI API (via LangChain).

This module provides a singleton client to interact with Google Gemini LLM using LangChain's ChatGoogleGenerativeAI.
It handles connection setup, prompt formatting, error handling, and logging for all LLM interactions.
"""

import logging
import time
from typing import Any

from langchain_google_genai import ChatGoogleGenerativeAI

from robbot.config.settings import settings
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)

_singleton: dict[str, "GeminiClient | None"] = {"client": None}


class GeminiClient:
    """
    GeminiClient wraps LangChain's ChatGoogleGenerativeAI for Google Gemini LLM access.

    Responsibilities:
    - Configure connection to Gemini API using API key and model settings
    - Generate LLM responses with context and prompt composition
    - Handle errors and log all interactions
    - Provide a singleton interface for the application
    """

    def __init__(self):
        """
        Initialize GeminiClient using LangChain's ChatGoogleGenerativeAI.

        Args:
            tools: Reserved for future function calling support (currently unused)
        Raises:
            LLMError: If initialization fails
        """
        try:
            self.llm = ChatGoogleGenerativeAI(
                model=settings.GEMINI_MODEL,
                temperature=settings.GEMINI_TEMPERATURE,
                max_output_tokens=settings.GEMINI_MAX_TOKENS,
                google_api_key=settings.GOOGLE_API_KEY,
                timeout=60,  # 60 seconds timeout
            )
            logger.info(
                "[SUCCESS] GeminiClient initialized via LangChain (model=%s, temp=%s)",
                settings.GEMINI_MODEL,
                settings.GEMINI_TEMPERATURE,
            )
        except Exception as e:
            logger.error("[ERROR] Failed to initialize GeminiClient: %s", e)
            raise LLMError("Gemini", f"Initialization failed: {e}", original_error=e) from e

    def generate_response(
        self,
        prompt: str,
        context: str | None = None,
    ) -> dict[str, Any]:
        """
        Generate a response from Gemini LLM using LangChain.

        Args:
            prompt: Main user prompt (str)
            context: Optional context string (conversation history, docs, etc)
            max_retries: Not used (for interface compatibility)

        Returns:
            dict: {
                "response": str (LLM output),
                "tokens_used": None (not available),
                "latency_ms": int (response time in ms),
                "model": str (model name),
                "finish_reason": None (not available)
            }

        Raises:
            LLMError: On any error from LangChain or Gemini
        """
        full_prompt = self._build_full_prompt(prompt, context)
        try:
            logger.info("[INFO] Generating Gemini response via LangChain")
            start_time = time.time()
            response = self.llm.invoke(full_prompt)
            latency_ms = int((time.time() - start_time) * 1000)
            response_text = response.content if hasattr(response, "content") else str(response)
            logger.info("[SUCCESS] Response generated via LangChain (%sms)", latency_ms)
            return {
                "response": response_text,
                "tokens_used": None,
                "latency_ms": latency_ms,
                "model": settings.GEMINI_MODEL,
                "finish_reason": None,
            }
        except Exception as e:
            logger.error("[ERROR] Unexpected error calling Gemini via LangChain: %s", e, exc_info=True)
            raise LLMError("Gemini", f"Unexpected error: {e}", original_error=e) from e

    def _build_full_prompt(self, prompt: str, context: str | None) -> str:
        """
        Compose the full prompt for Gemini, optionally including context.

        Args:
            prompt: Main user prompt (str)
            context: Optional context string

        Returns:
            str: Formatted prompt for LLM
        """
        if context:
            return f"{context}\n\n---\n\n{prompt}"
        return prompt

    def _extract_token_count(self, _response: Any) -> int:
        """
        Extract token count from Gemini response (not supported in LangChain integration).

        Args:
            response: Gemini response object

        Returns:
            int: Estimated or reported token count (always 0 here)
        """
        # Not supported in LangChain Google GenAI integration
        return 0

    def _extract_finish_reason(self, _response: Any) -> str:
        """
        Extract finish reason from Gemini response (not supported in LangChain integration).

        Args:
            response: Gemini response object

        Returns:
            str: Finish reason (always 'UNKNOWN' here)
        """
        # Not supported in LangChain Google GenAI integration
        return "UNKNOWN"


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
