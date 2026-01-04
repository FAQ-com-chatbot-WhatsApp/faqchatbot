"""
Gemini AI client for Google Generative AI API.

Este módulo fornece cliente HTTP para interagir com o Gemini API,
incluindo retry logic e logging de todas as interações.
"""

import logging
import time
from typing import Any

import google.generativeai as genai
from google.api_core import exceptions as google_exceptions

from robbot.config.settings import settings
from robbot.core.custom_exceptions import LLMError

logger = logging.getLogger(__name__)


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
            genai.configure(api_key=settings.GOOGLE_API_KEY)

            self.model = genai.GenerativeModel(
                model_name=settings.GEMINI_MODEL,
                generation_config={
                    "temperature": settings.GEMINI_TEMPERATURE,
                    "max_output_tokens": settings.GEMINI_MAX_TOKENS,
                },
                tools=tools
            )

            logger.info(
                f"[SUCCESS] GeminiClient inicializado (model={settings.GEMINI_MODEL}, "
                f"temp={settings.GEMINI_TEMPERATURE}, tools={len(tools) if tools else 0})"
            )
        except Exception as e:  # noqa: BLE001
            logger.error("[ERROR] Falha ao inicializar GeminiClient: %s", e)
            raise LLMError("Gemini", f"Initialization failed: {e}", original_error=e)

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
                    f"🤖 Gerando resposta Gemini (tentativa {attempt}/{max_retries})",
                    extra={"prompt_length": len(full_prompt)}
                )

                start_time = time.time()

                # Chamar Gemini API
                response = self.model.generate_content(full_prompt)

                latency_ms = int((time.time() - start_time) * 1000)

                # Extrair texto da resposta
                response_text = response.text if hasattr(response, 'text') else str(response)

                # Extrair metadados
                tokens_used = self._extract_token_count(response)
                finish_reason = self._extract_finish_reason(response)

                logger.info(
                    f"[SUCCESS] Resposta gerada com sucesso ({latency_ms}ms, {tokens_used} tokens)",
                    extra={
                        "latency_ms": latency_ms,
                        "tokens": tokens_used,
                        "model": settings.GEMINI_MODEL,
                    }
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
                wait_time = 2 ** attempt  # Exponential backoff
                logger.warning(
                    f"[WARNING] Rate limit atingido, aguardando {wait_time}s (tentativa {attempt})"
                )
                if attempt < max_retries:
                    time.sleep(wait_time)
                    continue
                raise LLMError("Gemini", f"Rate limit exceeded: {e}", original_error=e)

            except google_exceptions.DeadlineExceeded as e:
                # Timeout - tentar novamente
                logger.warning("[WARNING] Timeout na requisição (tentativa %s)", attempt)
                if attempt < max_retries:
                    continue
                raise LLMError("Gemini", f"Timeout: {e}", original_error=e)

            except google_exceptions.GoogleAPIError as e:
                # Erro da API Google
                logger.error(f"[ERROR] Erro Gemini API: {e}", exc_info=True)
                if attempt < max_retries:
                    time.sleep(1)
                    continue
                raise LLMError("Gemini", f"API error: {e}", original_error=e)

            except Exception as e:  # noqa: BLE001
                # Erro inesperado
                logger.error(f"[ERROR] Erro inesperado ao chamar Gemini: {e}", exc_info=True)
                if attempt < max_retries:
                    time.sleep(1)
                    continue
                raise LLMError("Gemini", f"Unexpected error: {e}", original_error=e)

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
            if hasattr(response, 'usage_metadata'):
                metadata = response.usage_metadata
                # Total = input + output tokens
                return (
                    getattr(metadata, 'prompt_token_count', 0) +
                    getattr(metadata, 'candidates_token_count', 0)
                )
        except (AttributeError, TypeError):
            pass

        # Fallback: estimar baseado em caracteres
        # Aproximação: 1 token ~= 4 caracteres
        return len(response.text) // 4 if hasattr(response, 'text') else 0

    def _extract_finish_reason(self, response: Any) -> str:
        """
        Extrair finish_reason da resposta.
        
        Args:
            response: Resposta do Gemini
            
        Returns:
            Motivo de término
        """
        try:
            if hasattr(response, 'candidates') and response.candidates:
                candidate = response.candidates[0]
                if hasattr(candidate, 'finish_reason'):
                    return str(candidate.finish_reason)
        except (AttributeError, IndexError, TypeError):
            pass

        return "UNKNOWN"


# Singleton global
_gemini_client: GeminiClient | None = None


def get_gemini_client(tools: list | None = None) -> GeminiClient:
    """
    Obter instância singleton do cliente Gemini.
    
    Args:
        tools: Lista de tools para function calling (opcional)
    
    Returns:
        GeminiClient singleton
    """
    global _gemini_client

    if _gemini_client is None:
        _gemini_client = GeminiClient(tools=tools)
        logger.info("🎯 GeminiClient inicializado como singleton")

    return _gemini_client


def close_gemini_client() -> None:
    """Fechar cliente (cleanup)."""
    global _gemini_client
    if _gemini_client is not None:
        logger.info("Fechando GeminiClient")
        _gemini_client = None
