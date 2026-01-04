"""
Dashboard Analytics Repository

Repositório especializado em agregações para dashboard principal.
"""

from datetime import datetime
from typing import Any

from sqlalchemy import text
from sqlalchemy.orm import Session

from robbot.adapters.repositories.analytics.performance_analytics_repository import (
    PerformanceAnalyticsRepository,
)


class DashboardAnalyticsRepository:
    """Repository para sumários e dashboards"""

    def __init__(self, db_session: Session):
        self.db = db_session
        self.performance_repo = PerformanceAnalyticsRepository(db_session)

    def get_dashboard_summary(
        self,
        start_date: datetime,
        end_date: datetime,
    ) -> dict[str, Any]:
        """
        Resumo completo para dashboard principal.

        Returns:
            {
                "total_leads": 150,
                "converted_leads": 45,
                "conversion_rate": 30.0,
                "avg_response_time_seconds": 180.5,
                "total_conversations": 200,
                "active_conversations": 25,
                "total_messages": 3500,
                "avg_messages_per_conversation": 17.5
            }
        """
        # Query agregada otimizada
        query = text("""
            SELECT
                COUNT(DISTINCT l.id) as total_leads,
                COUNT(DISTINCT CASE WHEN l.status = 'CONVERTED' THEN l.id END) as converted_leads,
                COUNT(DISTINCT c.id) as total_conversations,
                COUNT(DISTINCT CASE WHEN c.status IN ('ACTIVE', 'WAITING') THEN c.id END) as active_conversations,
                COUNT(m.id) as total_messages,
                AVG(msg_count.message_count) as avg_messages_per_conversation
            FROM leads l
            LEFT JOIN conversations c ON l.id = c.lead_id
            LEFT JOIN conversation_messages m ON c.id = m.conversation_id
            LEFT JOIN (
                SELECT conversation_id, COUNT(*) as message_count
                FROM conversation_messages
                GROUP BY conversation_id
            ) msg_count ON c.id = msg_count.conversation_id
            WHERE l.created_at >= :start_date
                AND l.created_at <= :end_date
                AND l.deleted_at IS NULL
        """)

        result = self.db.execute(query, {"start_date": start_date, "end_date": end_date})
        row = result.fetchone()

        if not row:
            return {}

        total_leads = row.total_leads or 0
        converted_leads = row.converted_leads or 0
        conversion_rate = (
            (converted_leads / total_leads * 100) if total_leads > 0 else 0.0
        )

        # Buscar tempo de resposta separadamente (query complexa)
        response_time_stats = self.performance_repo.get_response_time_stats(
            start_date, end_date
        )

        return {
            "total_leads": total_leads,
            "converted_leads": converted_leads,
            "conversion_rate": round(conversion_rate, 2),
            "avg_response_time_seconds": response_time_stats["avg_seconds"],
            "total_conversations": row.total_conversations or 0,
            "active_conversations": row.active_conversations or 0,
            "total_messages": row.total_messages or 0,
            "avg_messages_per_conversation": round(
                float(row.avg_messages_per_conversation or 0), 2
            ),
        }
