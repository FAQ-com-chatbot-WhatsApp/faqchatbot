"""
Bot Performance Analytics Repository

Repositório especializado em métricas de performance do bot.
"""

from datetime import datetime
from typing import Any

from sqlalchemy import case, func
from sqlalchemy.orm import Session

from robbot.infra.db.models.conversation_model import ConversationModel


class BotPerformanceAnalyticsRepository:
    """Repository para métricas de autonomia e eficácia do bot"""

    def __init__(self, db_session: Session):
        self.db = db_session

    def get_bot_autonomy_rate(
        self,
        start_date: datetime,
        end_date: datetime,
    ) -> dict[str, Any]:
        """
        Taxa de autonomia do bot (conversas resolvidas sem humano).

        Returns:
            {
                "total_conversations": 200,
                "bot_only": 120,
                "with_handoff": 80,
                "autonomy_rate": 60.0
            }
        """
        query = (
            self.db.query(
                func.count(ConversationModel.id).label("total"),  # type: ignore[misc]
                func.count(  # type: ignore[misc]
                    case((ConversationModel.handoff_at.is_(None), ConversationModel.id))
                ).label("bot_only"),
                func.count(  # type: ignore[misc]
                    case(
                        (ConversationModel.handoff_at.isnot(None), ConversationModel.id)
                    )
                ).label("with_handoff"),
            )
            .filter(
                ConversationModel.created_at >= start_date,
                ConversationModel.created_at <= end_date,
            )
        )

        result = query.first()

        if not result or result.total == 0:
            return {
                "total_conversations": 0,
                "bot_only": 0,
                "with_handoff": 0,
                "autonomy_rate": 0.0,
            }

        total = result.total
        bot_only = result.bot_only or 0
        with_handoff = result.with_handoff or 0
        autonomy_rate = (bot_only / total * 100) if total > 0 else 0.0

        return {
            "total_conversations": total,
            "bot_only": bot_only,
            "with_handoff": with_handoff,
            "autonomy_rate": round(autonomy_rate, 2),
        }
