"""
Gemini AI client for Google Generative AI API (via LangChain).

This module provides a singleton client to interact with Google Gemini LLM using LangChain's ChatGoogleGenerativeAI.
It handles connection setup, prompt formatting, error handling, and logging for all LLM interactions.
Implements automatic fallback to alternative models when quota limits are reached.
"""

import ast
import json
import logging
import time
from typing import Any

from langchain_google_genai import ChatGoogleGenerativeAI

from robbot.config.settings import settings
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)

_singleton: dict[str, "GeminiClient | None"] = {"client": None}

# Lista de modelos para fallback automático quando quota é excedida
# Ordem: flash (rápido) -> lite (leve) -> experimental -> pro (mais poderoso)
FALLBACK_MODELS = [
    "gemini-flash-latest",          # Alias para versão mais recente do flash
    "gemini-2.0-flash-lite",        # Versão lite, menor quota usage
    "gemini-2.0-flash",             # Versão 2.0 estável
    "gemini-exp-1206",              # Modelo experimental
    "gemini-3-flash-preview",       # Preview versão 3
    "gemini-pro-latest",            # Pro com maior capacidade
    "gemini-2.5-flash-lite",        # Lite da versão 2.5
]


class GeminiClient:
    """
    GeminiClient wraps LangChain's ChatGoogleGenerativeAI for Google Gemini LLM access.

    Responsibilities:
    - Configure connection to Gemini API using API key and model settings
    - Generate LLM responses with context and prompt composition
    - Automatic fallback to alternative models when quota is exceeded
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
            # Modelo primário vem das configurações
            self.primary_model = settings.GEMINI_MODEL
            self.llm = ChatGoogleGenerativeAI(
                model=self.primary_model,
                temperature=settings.GEMINI_TEMPERATURE,
                max_output_tokens=settings.GEMINI_MAX_TOKENS,
                google_api_key=settings.GOOGLE_API_KEY,
                timeout=60,  # 60 seconds timeout
            )
            self.current_model = self.primary_model
            logger.info(
                "[SUCCESS] GeminiClient initialized via LangChain (model=%s, temp=%s)",
                self.primary_model,
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
        Generate a response from Gemini LLM using LangChain with automatic fallback.

        Tenta usar o modelo primário. Se falhar por quota (429 RESOURCE_EXHAUSTED),
        automaticamente tenta modelos alternativos da lista FALLBACK_MODELS.

        Args:
            prompt: Main user prompt (str)
            context: Optional context string (conversation history, docs, etc)
            max_retries: Not used (for interface compatibility)

        Returns:
            dict: {
                "response": str (LLM output),
                "tokens_used": None (not available),
                "latency_ms": int (response time in ms),
                "model": str (model name usado),
                "finish_reason": None (not available)
            }

        Raises:
            LLMError: Se todos os modelos falharem ou erro não relacionado a quota
        """
        full_prompt = self._build_full_prompt(prompt, context)
        
        # Lista de modelos para tentar: primário + fallbacks
        models_to_try = [self.primary_model] + [m for m in FALLBACK_MODELS if m != self.primary_model]
        last_error = None

        for model_name in models_to_try:
            try:
                # Se não for o modelo atual, reconfigura o LLM
                if model_name != self.current_model:
                    logger.info("[FALLBACK] Tentando modelo alternativo: %s", model_name)
                    self.llm = ChatGoogleGenerativeAI(
                        model=model_name,
                        temperature=settings.GEMINI_TEMPERATURE,
                        max_output_tokens=settings.GEMINI_MAX_TOKENS,
                        google_api_key=settings.GOOGLE_API_KEY,
                        timeout=60,
                    )
                    self.current_model = model_name

                logger.info("[INFO] Generating Gemini response via LangChain (model=%s)", self.current_model)
                start_time = time.time()
                response = self.llm.invoke(full_prompt)
                latency_ms = int((time.time() - start_time) * 1000)

                # Handle response.content (can be str, list, dict, or other types)
                if hasattr(response, "content"):
                    if isinstance(response.content, list):
                        response_text = " ".join(str(item) for item in response.content)
                    elif isinstance(response.content, dict):
                        # Extract text from dict (handles {'type': 'text', 'text': '...'})
                        response_text = response.content.get("text", str(response.content))
                    else:
                        response_text = str(response.content)
                else:
                    response_text = str(response)

                # Normalize dict-like strings if model returns a serialized payload
                if isinstance(response_text, str) and response_text.lstrip().startswith("{") and "'text'" in response_text:
                    try:
                        parsed = ast.literal_eval(response_text)
                        if isinstance(parsed, dict) and "text" in parsed:
                            response_text = parsed["text"]
                    except (ValueError, SyntaxError):
                        pass

                logger.info("[SUCCESS] Response generated via LangChain (model=%s, %sms)", self.current_model, latency_ms)
                return {
                    "response": response_text,
                    "tokens_used": None,
                    "latency_ms": latency_ms,
                    "model": self.current_model,
                    "finish_reason": None,
                }

            except Exception as e:
                error_str = str(e)
                last_error = e

                # Verifica se é erro de quota (429 RESOURCE_EXHAUSTED)
                if "429" in error_str and "RESOURCE_EXHAUSTED" in error_str:
                    logger.warning(
                        "[QUOTA] Modelo %s atingiu limite de quota: %s",
                        model_name,
                        error_str[:200]  # Trunca erro para log
                    )
                    # Continua para próximo modelo
                    continue
                else:
                    # Erro não relacionado a quota - falha imediatamente
                    logger.error("[ERROR] Unexpected error calling Gemini via LangChain: %s", e, exc_info=True)
                    raise LLMError("Gemini", f"Unexpected error: {e}", original_error=e) from e

        # Se chegou aqui, todos os modelos falharam por quota
        logger.error("[QUOTA] Todos os modelos Gemini esgotaram a quota. Modelos tentados: %s", models_to_try)
        raise LLMError(
            "Gemini",
            f"Todos os modelos atingiram limite de quota. Tente novamente mais tarde. Modelos tentados: {', '.join(models_to_try)}",
            original_error=last_error
        )

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

    def _mock_response(self, prompt: str) -> str:
        """
        Return a deterministic mock response for DEV_MODE.

        Args:
            prompt: Full prompt text

        Returns:
            str: Mock response content
        """
        normalized_prompt = prompt.lower()

        if "responda apenas em json" in normalized_prompt and '"intent"' in prompt:
            return json.dumps(
                {
                    "intent": "OUTRO",
                    "spin_phase": "SITUATION",
                    "confidence": 50,
                }
            )

        if "responda apenas em json" in normalized_prompt and '"name"' in prompt:
            return json.dumps(
                {
                    "name": None,
                    "confidence": 0,
                    "source": "none",
                }
            )

        if "gere uma pergunta natural para descobrir o nome" in normalized_prompt:
            return json.dumps(
                {
                    "should_ask": False,
                    "name_request": None,
                }
            )

        if "gere uma resposta seguindo metodologia spin selling" in normalized_prompt:
            return "Oi! Entendi. Pode me contar um pouco mais sobre o que voce esta sentindo?"

        if "gere resposta de fallback" in normalized_prompt:
            return "Desculpe, tive uma dificuldade tecnica. O que voce gostaria de resolver hoje?"

        return "Oi! Entendi. Pode me contar um pouco mais para eu te ajudar?"


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
