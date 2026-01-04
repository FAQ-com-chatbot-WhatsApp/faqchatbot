"""
Performance Analytics Repository

Repositório especializado em métricas de performance e tempo de resposta.
"""

from datetime import datetime
from typing import Any
from uuid import UUID

from sqlalchemy import text
from sqlalchemy.orm import Session


class PerformanceAnalyticsRepository:
    """Repository para métricas de performance e velocidade de atendimento"""

    def __init__(self, db_session: Session):
        self.db = db_session

    def get_response_time_stats(
        self,
        start_date: datetime,
        end_date: datetime,
        user_id: UUID | None = None,
    ) -> dict[str, Any]:
        """
        Estatísticas de tempo de resposta (humano).

        Calcula tempo entre mensagem do lead e primeira resposta humana.

        Returns:
            {
                "avg_seconds": 180.5,
                "median_seconds": 120.0,
                "p95_seconds": 600.0,
                "p99_seconds": 1200.0,
                "total_responses": 350
            }
        """
        # Query complexa com window function para achar primeira resposta após mensagem do lead
        query = text("""
            WITH response_times AS (
                SELECT
                    c.id as conversation_id,
                    EXTRACT(EPOCH FROM (
                        MIN(m_out.created_at) - m_in.created_at
                    )) as response_seconds
                FROM conversation_messages m_in
                JOIN conversations c ON m_in.conversation_id = c.id
                JOIN conversation_messages m_out ON m_out.conversation_id = c.id
                    AND m_out.direction = 'OUTGOING'
                    AND m_out.created_at > m_in.created_at
                WHERE m_in.direction = 'INCOMING'
                    AND c.handoff_at IS NOT NULL
                    AND m_in.created_at >= :start_date
                    AND m_in.created_at <= :end_date
                    AND (:user_id::uuid IS NULL OR c.handoff_to = :user_id::uuid)
                GROUP BY c.id, m_in.id, m_in.created_at
            )
            SELECT
                AVG(response_seconds) as avg_seconds,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY response_seconds) as median_seconds,
                PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY response_seconds) as p95_seconds,
                PERCENTILE_CONT(0.99) WITHIN GROUP (ORDER BY response_seconds) as p99_seconds,
                COUNT(*) as total_responses
            FROM response_times
            WHERE response_seconds >= 0
        """)

        result = self.db.execute(
            query,
            {
                "start_date": start_date,
                "end_date": end_date,
                "user_id": str(user_id) if user_id else None,
            },
        )
        row = result.fetchone()

        if not row or row.avg_seconds is None:
            return {
                "avg_seconds": 0.0,
                "median_seconds": 0.0,
                "p95_seconds": 0.0,
                "p99_seconds": 0.0,
                "total_responses": 0,
            }

        return {
            "avg_seconds": round(float(row.avg_seconds), 2),
            "median_seconds": round(float(row.median_seconds), 2),
            "p95_seconds": round(float(row.p95_seconds), 2),
            "p99_seconds": round(float(row.p99_seconds), 2),
            "total_responses": row.total_responses,
        }

    def get_message_volume(
        self,
        start_date: datetime,
        end_date: datetime,
        granularity: str = "day",  # hour, day, week
    ) -> list[dict[str, Any]]:
        """
        Volume de mensagens ao longo do tempo.

        Returns:
            [
                {
                    "timestamp": "2024-12-18T00:00:00",
                    "incoming": 150,
                    "outgoing": 180,
                    "total": 330
                },
                ...
            ]
        """
        if granularity == "hour":
            trunc = "hour"
        elif granularity == "week":
            trunc = "week"
        else:
            trunc = "day"

        query = text(
            """
            SELECT
                date_trunc(:granularity, created_at) as timestamp,
                COUNT(*) FILTER (WHERE direction = 'INCOMING') as incoming,
                COUNT(*) FILTER (WHERE direction = 'OUTGOING') as outgoing,
                COUNT(*) as total
            FROM conversation_messages
            WHERE created_at >= :start_date
                AND created_at <= :end_date
            GROUP BY timestamp
            ORDER BY timestamp
        """
        )

        result = self.db.execute(
            query, {"granularity": trunc, "start_date": start_date, "end_date": end_date}
        )
        rows = result.fetchall()

        return [
            {
                "timestamp": row.timestamp.isoformat(),
                "incoming": row.incoming or 0,
                "outgoing": row.outgoing or 0,
                "total": row.total or 0,
            }
            for row in rows
        ]
