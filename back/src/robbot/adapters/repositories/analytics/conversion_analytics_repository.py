"""
Conversion Analytics Repository

Repositório especializado em métricas de conversão de leads.
"""

from datetime import datetime
from typing import Any

from sqlalchemy import case, func, text
from sqlalchemy.orm import Session

from robbot.domain.enums import LeadStatus
from robbot.infra.db.models.lead_model import LeadModel
from robbot.infra.db.models.user_model import UserModel


class ConversionAnalyticsRepository:
    """Repository para métricas de conversão e funil de vendas"""

    def __init__(self, db_session: Session):
        self.db = db_session

    def get_conversion_rate(
        self,
        start_date: datetime,
        end_date: datetime,
        segment_by: str | None = None,
    ) -> dict[str, Any]:
        """
        Calcula taxa de conversão global ou segmentada.

        Args:
            start_date: Data inicial
            end_date: Data final
            segment_by: Segmentação (procedure, source, assigned_to)

        Returns:
            {
                "total_leads": 150,
                "converted_leads": 45,
                "conversion_rate": 30.0,
                "segments": [...]  # se segment_by
            }
        """
        # Query base
        query = self.db.query(
            func.count(LeadModel.id).label("total_leads"),
            func.count(
                case((LeadModel.status == LeadStatus.CONVERTED, LeadModel.id))
            ).label("converted_leads"),
        ).filter(
            LeadModel.created_at >= start_date,
            LeadModel.created_at <= end_date,
            LeadModel.deleted_at.is_(None),
        )

        if segment_by and segment_by == "assigned_to":
            query = query.join(
                UserModel, LeadModel.assigned_to_user_id == UserModel.id
            )
            query = query.add_columns(
                UserModel.full_name.label("segment_name"),
                UserModel.id.label("segment_id"),
            )
            query = query.group_by(UserModel.id, UserModel.full_name)
            # Adicionar outros segments futuramente

        result = query.all()

        if not segment_by:
            row = result[0] if result else (0, 0)
            total = row.total_leads or 0
            converted = row.converted_leads or 0
            rate = (converted / total * 100) if total > 0 else 0.0

            return {
                "total_leads": total,
                "converted_leads": converted,
                "conversion_rate": round(rate, 2),
            }
        else:
            segments = []
            for row in result:
                total = row.total_leads or 0
                converted = row.converted_leads or 0
                rate = (converted / total * 100) if total > 0 else 0.0

                segments.append(
                    {
                        "segment_name": row.segment_name,
                        "segment_id": str(row.segment_id),
                        "total_leads": total,
                        "converted_leads": converted,
                        "conversion_rate": round(rate, 2),
                    }
                )

            return {"segments": segments}

    def get_conversion_funnel(
        self,
        start_date: datetime,
        end_date: datetime,
    ) -> dict[str, Any]:
        """
        Analisa funil de conversão com drop-off por etapa.

        Etapas:
        1. Leads criados (NEW)
        2. Engajados (respondeu pelo menos 1 msg)
        3. Qualificados (maturity_score >= 60)
        4. Handoff (transferido para humano)
        5. Convertidos (CONVERTED)

        Returns:
            {
                "stages": [
                    {
                        "stage": "created",
                        "count": 150,
                        "percentage": 100.0,
                        "drop_off": 0.0
                    },
                    ...
                ]
            }
        """
        # CTE para calcular cada etapa
        query = text("""
            WITH funnel AS (
                SELECT
                    COUNT(DISTINCT l.id) as total_created,
                    COUNT(DISTINCT CASE WHEN cm.id IS NOT NULL THEN l.id END) as total_engaged,
                    COUNT(DISTINCT CASE WHEN l.maturity_score >= 60 THEN l.id END) as total_qualified,
                    COUNT(DISTINCT CASE WHEN c.handoff_at IS NOT NULL THEN l.id END) as total_handoff,
                    COUNT(DISTINCT CASE WHEN l.status = 'CONVERTED' THEN l.id END) as total_converted
                FROM leads l
                LEFT JOIN conversations c ON l.id = c.lead_id
                LEFT JOIN conversation_messages cm ON c.id = cm.conversation_id
                    AND cm.direction = 'INCOMING'
                WHERE l.created_at >= :start_date
                    AND l.created_at <= :end_date
                    AND l.deleted_at IS NULL
            )
            SELECT * FROM funnel
        """)

        result = self.db.execute(query, {"start_date": start_date, "end_date": end_date})
        row = result.fetchone()

        if not row:
            return {"stages": []}

        total_created = row.total_created or 0

        stages = [
            {
                "stage": "created",
                "name": "Leads Criados",
                "count": total_created,
                "percentage": 100.0,
                "drop_off": 0.0,
            },
            {
                "stage": "engaged",
                "name": "Engajados (responderam)",
                "count": row.total_engaged or 0,
                "percentage": round(
                    (row.total_engaged or 0) / total_created * 100, 2
                )
                if total_created > 0
                else 0,
                "drop_off": round(
                    (total_created - (row.total_engaged or 0)) / total_created * 100, 2
                )
                if total_created > 0
                else 0,
            },
            {
                "stage": "qualified",
                "name": "Qualificados (score >= 60)",
                "count": row.total_qualified or 0,
                "percentage": round(
                    (row.total_qualified or 0) / total_created * 100, 2
                )
                if total_created > 0
                else 0,
                "drop_off": round(
                    ((row.total_engaged or 0) - (row.total_qualified or 0))
                    / (row.total_engaged or 0)
                    * 100,
                    2,
                )
                if row.total_engaged
                else 0,
            },
            {
                "stage": "handoff",
                "name": "Transferidos para humano",
                "count": row.total_handoff or 0,
                "percentage": round(
                    (row.total_handoff or 0) / total_created * 100, 2
                )
                if total_created > 0
                else 0,
                "drop_off": round(
                    ((row.total_qualified or 0) - (row.total_handoff or 0))
                    / (row.total_qualified or 0)
                    * 100,
                    2,
                )
                if row.total_qualified
                else 0,
            },
            {
                "stage": "converted",
                "name": "Convertidos (agendaram)",
                "count": row.total_converted or 0,
                "percentage": round(
                    (row.total_converted or 0) / total_created * 100, 2
                )
                if total_created > 0
                else 0,
                "drop_off": round(
                    ((row.total_handoff or 0) - (row.total_converted or 0))
                    / (row.total_handoff or 0)
                    * 100,
                    2,
                )
                if row.total_handoff
                else 0,
            },
        ]

        return {"stages": stages}

    def get_time_to_conversion(
        self,
        start_date: datetime,
        end_date: datetime,
    ) -> dict[str, float]:
        """
        Calcula estatísticas de tempo até conversão.

        Returns:
            {
                "avg_hours": 48.5,
                "median_hours": 36.0,
                "min_hours": 2.0,
                "max_hours": 168.0,
                "p95_hours": 120.0
            }
        """
        query = text("""
            SELECT
                AVG(EXTRACT(EPOCH FROM (l.converted_at - l.created_at)) / 3600) as avg_hours,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (l.converted_at - l.created_at)) / 3600) as median_hours,
                MIN(EXTRACT(EPOCH FROM (l.converted_at - l.created_at)) / 3600) as min_hours,
                MAX(EXTRACT(EPOCH FROM (l.converted_at - l.created_at)) / 3600) as max_hours,
                PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (l.converted_at - l.created_at)) / 3600) as p95_hours
            FROM leads l
            WHERE l.status = 'CONVERTED'
                AND l.converted_at IS NOT NULL
                AND l.created_at >= :start_date
                AND l.created_at <= :end_date
                AND l.deleted_at IS NULL
        """)

        result = self.db.execute(query, {"start_date": start_date, "end_date": end_date})
        row = result.fetchone()

        if not row or row.avg_hours is None:
            return {
                "avg_hours": 0.0,
                "median_hours": 0.0,
                "min_hours": 0.0,
                "max_hours": 0.0,
                "p95_hours": 0.0,
            }

        return {
            "avg_hours": round(float(row.avg_hours), 2),
            "median_hours": round(float(row.median_hours), 2),
            "min_hours": round(float(row.min_hours), 2),
            "max_hours": round(float(row.max_hours), 2),
            "p95_hours": round(float(row.p95_hours), 2),
        }
