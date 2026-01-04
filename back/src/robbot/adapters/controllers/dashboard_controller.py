"""
Dashboard Controller - MVP KISS

Apenas 3 endpoints essenciais para dashboard básico.
"""

from datetime import datetime, timedelta

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.orm import Session

from robbot.adapters.repositories.analytics.bot_performance_analytics_repository import (
    BotPerformanceAnalyticsRepository,
)
from robbot.adapters.repositories.analytics.conversion_analytics_repository import (
    ConversionAnalyticsRepository,
)
from robbot.adapters.repositories.analytics.dashboard_analytics_repository import (
    DashboardAnalyticsRepository,
)
from robbot.adapters.repositories.analytics.performance_analytics_repository import (
    PerformanceAnalyticsRepository,
)
from robbot.api.v1.dependencies import get_current_user, get_db
from robbot.domain.enums import Role
from robbot.infra.db.models.user_model import UserModel
from robbot.infra.redis.client import get_redis_client
from robbot.schemas.metrics_schemas import (
    BotAutonomyResponse,
    ConversionFunnelResponse,
    DashboardSummaryResponse,
)
from robbot.services.analytics.metrics_service import MetricsService

router = APIRouter(prefix="/metrics", tags=["Metrics"])
def get_metrics_service(db_session: Session = Depends(get_db)) -> MetricsService:
    """Injeta MetricsService com repositórios especializados"""
    return MetricsService(
        conversion_repo=ConversionAnalyticsRepository(db_session),
        performance_repo=PerformanceAnalyticsRepository(db_session),
        bot_performance_repo=BotPerformanceAnalyticsRepository(db_session),
        dashboard_repo=DashboardAnalyticsRepository(db_session),
        redis_client=get_redis_client(),
    )
def check_admin(current_user: UserModel = Depends(get_current_user)):
    """Apenas admin"""
    if current_user.role != Role.ADMIN:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Apenas admin")
    return current_user
def parse_dates(
    start_date: str | None, end_date: str | None, period: str
) -> tuple[datetime, datetime]:
    """Parse datas"""
    if start_date and end_date:
        return (
            datetime.fromisoformat(start_date).replace(hour=0, minute=0),
            datetime.fromisoformat(end_date).replace(hour=23, minute=59),
        )

    end = datetime.now().replace(hour=23, minute=59, second=59)
    days = {"7d": 7, "30d": 30, "90d": 90}.get(period, 30)
    return end - timedelta(days=days), end
@router.get("/dashboard", response_model=DashboardSummaryResponse)
def dashboard(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """KPIs: conversão, mensagens, tempo resposta. Cache 5min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_dashboard_summary(start, end)
@router.get("/conversion-funnel", response_model=ConversionFunnelResponse)
def funnel(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Funil 5 etapas + drop-off. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_conversion_funnel(start, end)
@router.get("/bot-autonomy", response_model=BotAutonomyResponse)
def bot_autonomy(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(check_admin),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Taxa autonomia bot. Admin only. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_bot_autonomy_rate(start, end)
