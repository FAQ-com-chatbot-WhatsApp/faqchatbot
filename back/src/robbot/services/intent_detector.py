"""
Intent Detector - Detecção de intenção, urgência e atualização de score.

Responsabilidades:
- Detectar intenção do cliente (INTERESSE_PRODUTO, ORÇAMENTO, etc)
- Detectar urgência na mensagem
- Extrair nome do cliente
- Atualizar maturity score do lead
- Verificar necessidade de escalação para humano
"""

import json
import logging
from typing import Any

from robbot.adapters.external.gemini_client import GeminiClient
from robbot.adapters.repositories.lead_repository import LeadRepository
from robbot.config.prompts import PromptTemplates
from robbot.core.custom_exceptions import DatabaseError, LLMError
from robbot.infra.db.models.conversation_model import ConversationModel

logger = logging.getLogger(__name__)


class IntentDetector:
    """Detecta intenções, urgência e gerencia score de maturidade"""

    def __init__(self, gemini_client: GeminiClient, prompt_templates: PromptTemplates):
        self.gemini_client = gemini_client
        self.prompt_templates = prompt_templates

    async def detect_intent(self, message: str, context: str) -> str:
        """
        Detectar intenção da mensagem do cliente.

        Args:
            message: Mensagem do cliente
            context: Contexto conversacional

        Returns:
            str: Intenção detectada (INTERESSE_PRODUTO, ORÇAMENTO, AGENDAMENTO, etc)

        Raises:
            LLMError: Se falhar ao detectar intenção
        """
        try:
            prompt = self.prompt_templates.format_intent_detection_prompt(message, context)
            response = self.gemini_client.generate_response(prompt)

            intent = response["response"].strip().upper()

            valid_intents = [
                "INTERESSE_PRODUTO",
                "ORCAMENTO",
                "AGENDAMENTO",
                "DUVIDA_TECNICA",
                "RECLAMACAO",
                "AGRADECIMENTO",
                "OUTRO",
            ]

            if intent not in valid_intents:
                intent = "OUTRO"

            logger.info("[SUCCESS] Intent detected: %s", intent)

            return intent

        except LLMError:
            raise
        except Exception as e:  # noqa: BLE001
            logger.warning("[WARNING] Failed to detect intent: %s", e)
            raise LLMError(f"Failed to detect intent: {e}")

    async def detect_urgency(self, message: str, context: str) -> bool:
        """
        Detectar se mensagem indica urgência.

        Args:
            message: Mensagem do cliente
            context: Contexto conversacional

        Returns:
            bool: True se urgente

        Raises:
            LLMError: Se falhar ao detectar urgência
        """
        try:
            prompt = self.prompt_templates.format_urgency_detection_prompt(message, context)
            response = self.gemini_client.generate_response(prompt)

            result = json.loads(response["response"].strip())
            is_urgent = result.get("urgent", False)

            if is_urgent:
                logger.info("[SUCCESS] Urgency detected: %s", result.get("reason", "unknown"))

            return is_urgent

        except (LLMError, json.JSONDecodeError, KeyError):
            # Se falhar parsing, assume não urgente
            logger.warning("[WARNING] Failed to detect urgency, assuming not urgent")
            return False

    async def try_extract_name(self, session: Any, message: str, context: str, conversation: ConversationModel) -> None:
        """
        Tentar extrair nome do paciente da mensagem de forma inteligente.
        Atualiza o lead se encontrar nome com confiança >= 70%.

        Args:
            session: Sessão do banco de dados
            message: Mensagem do cliente
            context: Contexto conversacional
            conversation: Conversa atual
        """
        try:
            prompt = self.prompt_templates.format_name_extraction_prompt(message, context)
            response = self.gemini_client.generate_response(prompt)

            # Parse JSON response
            result = json.loads(response["response"].strip())

            name = result.get("name")
            confidence = result.get("confidence", 0)

            if name and name != "null" and confidence >= 70:
                # Update lead name
                lead_repo = LeadRepository(session)
                conversation.lead.name = name
                lead_repo.update(conversation.lead)
                session.flush()

                logger.info("[SUCCESS] Name extracted: %s (confidence=%s%%)", name, confidence)

        except (LLMError, json.JSONDecodeError, KeyError) as e:
            logger.warning("[WARNING] Failed to extract name: %s", e)

    async def generate_name_request(self, context: str, maturity_score: int) -> str | None:
        """
        Gerar solicitação natural do nome do paciente.

        Args:
            context: Contexto conversacional
            maturity_score: Score de maturidade do lead

        Returns:
            str | None: Solicitação de nome ou None se não deve solicitar
        """
        try:
            prompt = self.prompt_templates.format_name_request_prompt(context, maturity_score)

            response = self.gemini_client.generate_response(prompt)

            result = json.loads(response["response"].strip())

            should_ask = result.get("should_ask", False)
            name_request = result.get("name_request")

            if should_ask and name_request:
                logger.info("[SUCCESS] Name request generated")
                return name_request

            return None

        except (LLMError, json.JSONDecodeError, KeyError) as e:
            logger.warning("[WARNING] Failed to generate name request: %s", e)
            return None

    async def update_maturity_score(
        self, session: Any, conversation: ConversationModel, message: str, intent: str
    ) -> int:
        """
        Atualizar score de maturidade do lead baseado na intenção.

        Score mapping:
        - INTERESSE_PRODUTO: +5
        - DUVIDA_TECNICA: +3
        - ORCAMENTO: +15
        - AGENDAMENTO: +20
        - RECLAMACAO: +0
        - AGRADECIMENTO: +1
        - OUTRO: +0

        Args:
            session: Sessão do banco de dados
            conversation: Conversa atual
            message: Mensagem do cliente
            intent: Intenção detectada

        Returns:
            int: Novo score de maturidade

        Raises:
            DatabaseError: Se falhar ao atualizar score
        """
        try:
            if not conversation.lead:
                return 0

            current_score = conversation.lead.maturity_score

            score_delta = {
                "INTERESSE_PRODUTO": 5,
                "DUVIDA_TECNICA": 3,
                "ORCAMENTO": 15,
                "AGENDAMENTO": 20,
                "RECLAMACAO": 0,
                "AGRADECIMENTO": 1,
            }.get(intent, 0)

            new_score = min(100, current_score + score_delta)

            if conversation.lead:
                lead_repo = LeadRepository(session)
                conversation.lead.maturity_score = new_score
                lead_repo.update(conversation.lead)
                session.flush()

            logger.info(
                "[SUCCESS] Score updated (lead_id=%s, %s → %s, delta=%s)",
                conversation.lead_id,
                current_score,
                new_score,
                score_delta,
            )

            return new_score

        except DatabaseError:
            raise
        except Exception as e:  # noqa: BLE001
            logger.warning("[WARNING] Failed to update score: %s", e)
            raise DatabaseError(f"Failed to update maturity score: {e}")

    async def check_escalation_needed(
        self,
        conversation: ConversationModel,
        intent: str,
        message: str,
        score: int,
    ) -> bool:
        """
        Verifica se precisa escalar para humano.

        Critérios de escalação:
        1. Score >= 85 (lead muito maduro)
        2. Cliente pede explicitamente falar com humano
        3. Intent OUTRO múltiplas vezes (bot confuso)

        Args:
            conversation: Conversa atual
            intent: Intenção detectada
            message: Mensagem do cliente
            score: Score de maturidade atual

        Returns:
            bool: True se deve escalar
        """
        # Critério 1: High score (lead ready)
        if score >= 85:
            logger.info("[INFO] Escalation needed: high score (%s) - conv=%s", score, conversation.id)
            return True

        # Critério 2: Cliente pede falar com humano
        human_keywords = [
            "falar com alguém",
            "atendente",
            "pessoa de verdade",
            "humano",
            "gerente",
            "supervisor",
        ]

        message_lower = message.lower()
        if any(keyword in message_lower for keyword in human_keywords):
            logger.info("[INFO] Escalation needed: client requested human - conv=%s", conversation.id)
            return True

        # Critério 3: Bot confuso (intent OUTRO múltiplas vezes)
        # TODO: Implementar contador de OUTRO consecutivos
        if intent == "OUTRO":
            logger.info("[WARNING] Intent OUTRO detected - may need escalation - conv=%s", conversation.id)

        return False
