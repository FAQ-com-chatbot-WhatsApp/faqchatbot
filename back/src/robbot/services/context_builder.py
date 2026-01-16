"""
Context Builder - Gerencia contexto conversacional via ChromaDB.

Responsabilidades:
- Recuperar histórico de conversas do ChromaDB
- Salvar novas interações no ChromaDB
- Formatar contexto para o LLM

REFACTORED: Dependency injection of VectorStore interface (Issue #3: Missing Abstractions)
"""

import logging
from typing import Any

from robbot.core.custom_exceptions import VectorDBError
from robbot.core.interfaces import VectorStore

logger = logging.getLogger(__name__)


class ContextBuilder:
    """Gerencia contexto conversacional via ChromaDB"""

    def __init__(self, vector_store: VectorStore):
        """
        Initialize ContextBuilder with DI.

        Args:
            vector_store: Injected VectorStore implementation (ChromaDB, Pinecone, etc.)
        """
        self.vector_store = vector_store

    async def get_conversation_context(self, conversation_id: str, limit: int = 5) -> str:
        """
        Recuperar contexto conversacional do ChromaDB.

        Args:
            conversation_id: ID da conversa
            limit: Número máximo de interações passadas

        Returns:
            str: Contexto formatado (vazio se sem histórico)

        Raises:
            VectorDBError: Se falhar ao acessar ChromaDB
        """
        try:
            results = await self.vector_store.search(conversation_id, limit=limit)

            if not results:
                return ""

            context_parts = [r.get("text", "") for r in results]
            context_text = "\n---\n".join(context_parts)

            logger.info("[SUCCESS] Context retrieved (%s documents)", len(results))

            return context_text

        except VectorDBError:
            raise
        except Exception as e:  # noqa: BLE001
            logger.warning("[WARNING] Failed to fetch context: %s", e)
            raise VectorDBError(f"Failed to get context: {e}")

    async def save_to_chroma(self, conversation_id: str, text: str, metadata: dict[str, Any]) -> None:
        """
        Persistir par de mensagens (User/Bot) no ChromaDB para contexto futuro.

        Args:
            conversation_id: ID da conversa
            text: Texto do par User/Bot
            metadata: Metadados (intent, score, etc)

        Raises:
            VectorDBError: Se falhar ao salvar no ChromaDB
        """
        try:
            await self.vector_store.add(conversation_id, text, metadata)
            logger.info("[SUCCESS] Context saved to ChromaDB (conv_id=%s)", conversation_id)

        except VectorDBError:
            raise
        except Exception as e:  # noqa: BLE001
            logger.warning("[WARNING] Failed to save to ChromaDB: %s", e)
            raise VectorDBError(f"Failed to save to ChromaDB: {e}")
