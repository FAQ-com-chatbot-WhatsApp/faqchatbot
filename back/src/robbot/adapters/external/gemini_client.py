"""
Gemini AI client for Google Generative AI API.

Este módulo fornece cliente HTTP para interagir com o Gemini API,
incluindo retry logic e logging de todas as interações.
"""

import logging
import time
from typing import Any

import google.genai as genai
from google.api_core import exceptions as google_exceptions

from robbot.config.settings import settings
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)

_singleton: dict[str, "GeminiClient | None"] = {"client": None}


class GeminiClient:
    """
    Client para Google Gemini API com retry logic e error handling.

    Responsabilidades:
    - Configurar conexão com Gemini API
    - Gerar respostas com contexto
    - Retry automático em caso de falhas transientes
    - Logging de todas as interações
    """

    def __init__(self, tools: list | None = None):
        """Inicializar cliente Gemini com configurações do settings."""
        try:
            self._tools = tools or []

            self.client = genai.Client(api_key=settings.GOOGLE_API_KEY)

            self._generation_config = {
                "temperature": settings.GEMINI_TEMPERATURE,
                "max_output_tokens": settings.GEMINI_MAX_TOKENS,
            }

            logger.info(
                "[SUCCESS] GeminiClient initialized (model=%s, temp=%s, tools=%s)",
                settings.GEMINI_MODEL,
                settings.GEMINI_TEMPERATURE,
                len(tools) if tools else 0,
            )
        except Exception as e:  # noqa: BLE001 (blind exception)
            logger.error("[ERROR] Failed to initialize GeminiClient: %s", e)
            raise LLMError("Gemini", f"Initialization failed: {e}", original_error=e) from e

    def generate_response(
        self,
        prompt: str,
        context: str | None = None,
        max_retries: int = 3,
    ) -> dict[str, Any]:
        """
        Gerar resposta do Gemini com retry logic.

        Args:
            prompt: Prompt principal para o LLM
            context: Contexto adicional (histórico, docs, etc.)
            max_retries: Número máximo de tentativas

        Returns:
            Dict com resposta e metadados:
            {
                "response": str,
                "tokens_used": int,
                "latency_ms": int,
                "model": str,
                "finish_reason": str
            }

        Raises:
            ExternalServiceError: Se todas as tentativas falharem
        """
        # Montar prompt completo
        full_prompt = self._build_full_prompt(prompt, context)

        for attempt in range(1, max_retries + 1):
            try:
                logger.info(
                    "[INFO] Generating Gemini response (attempt %s/%s)",
                    attempt,
                    max_retries,
                    extra={"prompt_length": len(full_prompt)},
                )

                start_time = time.time()

                # Montar config no formato do google-genai SDK v1+
                config = {
                    **(self._generation_config or {}),
                }
                
                if self._tools:
                    # O SDK espera [ {'function_declarations': [...] } ]
                    config["tools"] = [{"function_declarations": self._tools}]

                response = self.client.models.generate_content(
                    model=settings.GEMINI_MODEL,
                    contents=full_prompt,
                    config=config
                )

                latency_ms = int((time.time() - start_time) * 1000)

                # Extrair texto da resposta
                response_text = response.text if hasattr(response, "text") else str(response)

                # Extrair metadados
                tokens_used = self._extract_token_count(response)
                finish_reason = self._extract_finish_reason(response)

                logger.info(
                    "[SUCCESS] Response generated successfully (%sms, %s tokens)",
                    latency_ms,
                    tokens_used,
                    extra={
                        "latency_ms": latency_ms,
                        "tokens": tokens_used,
                        "model": settings.GEMINI_MODEL,
                    },
                )

                return {
                    "response": response_text,
                    "tokens_used": tokens_used,
                    "latency_ms": latency_ms,
                    "model": settings.GEMINI_MODEL,
                    "finish_reason": finish_reason,
                }

            except google_exceptions.ResourceExhausted as e:
                # Rate limit - aguardar e tentar novamente
                wait_time = 2**attempt  # Exponential backoff
                logger.warning("[WARNING] Rate limit reached, waiting %ss (attempt %s)", wait_time, attempt)
                if attempt < max_retries:
                    time.sleep(wait_time)
                    continue
                raise LLMError("Gemini", f"Rate limit exceeded: {e}", original_error=e) from e

            except google_exceptions.DeadlineExceeded as e:
                # Timeout - tentar novamente
                logger.warning("[WARNING] Request timeout (attempt %s)", attempt)
                if attempt < max_retries:
                    continue
                raise LLMError("Gemini", f"Timeout: {e}", original_error=e) from e

            except google_exceptions.GoogleAPIError as e:
                # Erro da API Google
                logger.error("[ERROR] Gemini API error: %s", e, exc_info=True)
                if attempt < max_retries:
                    time.sleep(1)
                    continue
                raise LLMError("Gemini", f"API error: {e}", original_error=e) from e

            except Exception as e:  # noqa: BLE001 (blind exception)
                # Erro inesperado
                logger.error("[ERROR] Unexpected error calling Gemini: %s", e, exc_info=True)
                if attempt < max_retries:
                    time.sleep(1)
                    continue
                raise LLMError("Gemini", f"Unexpected error: {e}", original_error=e) from e

        # Se chegou aqui, todas as tentativas falharam
        raise LLMError("Gemini", "All retry attempts failed")

    def _build_full_prompt(self, prompt: str, context: str | None) -> str:
        """
        Montar prompt completo com contexto.

        Args:
            prompt: Prompt principal
            context: Contexto adicional

        Returns:
            Prompt formatado
        """
        if context:
            return f"{context}\n\n---\n\n{prompt}"
        return prompt

    def _extract_token_count(self, response: Any) -> int:
        """
        Extrair contagem de tokens da resposta.

        Args:
            response: Resposta do Gemini

        Returns:
            Número de tokens usados
        """
        try:
            if hasattr(response, "usage_metadata"):
                metadata = response.usage_metadata
                # Total = input + output tokens
                return getattr(metadata, "prompt_token_count", 0) + getattr(metadata, "candidates_token_count", 0)
        except (AttributeError, TypeError):
            pass

        # Fallback: estimar baseado em caracteres
        # Aproximação: 1 token ~= 4 caracteres
        return len(response.text) // 4 if hasattr(response, "text") else 0

    def _extract_finish_reason(self, response: Any) -> str:
        """
        Extrair finish_reason da resposta.

        Args:
            response: Resposta do Gemini

        Returns:
            Motivo de término
        """
        try:
            if hasattr(response, "candidates") and response.candidates:
                candidate = response.candidates[0]
                if hasattr(candidate, "finish_reason"):
                    return str(candidate.finish_reason)
        except (AttributeError, IndexError, TypeError):
            pass

        return "UNKNOWN"


def get_gemini_client(tools: list | None = None) -> GeminiClient:
    """Obter instância singleton do cliente Gemini."""
    client = _singleton.get("client")
    if client is None:
        _singleton["client"] = GeminiClient(tools=tools)
        logger.info("GeminiClient initialized as singleton")
    return _singleton["client"]  # type: ignore[return-value]


def close_gemini_client() -> None:
    """Fechar cliente (cleanup)."""
    if _singleton.get("client") is not None:
        logger.info("Fechando GeminiClient")
    _singleton["client"] = None
