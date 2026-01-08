"""
Dashboard Controller - MVP KISS

Apenas 3 endpoints essenciais para dashboard básico.
Sprint 12 - L1: Adicionados endpoints de performance reports com exportação PDF/Excel.
"""

from datetime import datetime, timedelta

from fastapi import APIRouter, Depends, HTTPException, Query, WebSocket, WebSocketDisconnect, status
from fastapi.responses import Response
from sqlalchemy.orm import Session

from robbot.adapters.repositories.analytics_repository import AnalyticsRepository
from robbot.api.v1.dependencies import get_current_user, get_db
from robbot.domain.enums import Role
from robbot.infra.db.models.user_model import UserModel
from robbot.infra.redis.client import get_redis_client
from robbot.schemas.metrics_schemas import (
    BotAutonomyResponse,
    BotResponseTimeResponse,
    ConversationAnalysisReportSchema,
    ConversationsByStatusResponse,
    ConversionBySourceResponse,
    ConversionFunnelResponse,
    ConversionReportExtendedSchema,
    ConversionTrendResponse,
    DashboardSummaryResponse,
    HandoffRateResponse,
    LostLeadsAnalysisResponse,
    PeakHoursResponse,
    PerformanceReportSchema,
    RealtimeDashboardSchema,
    TimeToConversionExtendedResponse,
)
from robbot.services.analytics.metrics_service import MetricsService
from robbot.services.export_service import ExportService

router = APIRouter(prefix="/metrics", tags=["Metrics"])
def get_metrics_service(db_session: Session = Depends(get_db)) -> MetricsService:
    """Injeta MetricsService com repositórios especializados"""
    return MetricsService(
        analytics_repo=AnalyticsRepository(db_session),
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
# =============================================================================
# PERFORMANCE REPORTS (Sprint 12 - L1)
# =============================================================================

@router.get("/performance/bot-response-time", response_model=BotResponseTimeResponse)
def bot_response_time_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Tempo de resposta do bot via LLM latency. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_bot_response_time(start, end)
@router.get("/performance/handoff-rate", response_model=HandoffRateResponse)
def handoff_rate_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Taxa de resolução automática vs handoff. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_handoff_rate(start, end)
@router.get("/performance/peak-hours", response_model=PeakHoursResponse)
def peak_hours_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Horários de pico de atendimento. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_peak_hours(start, end)
@router.get("/performance/conversations-by-status", response_model=ConversationsByStatusResponse)
def conversations_by_status_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Distribuição de conversas por status. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_conversations_by_status(start, end)
@router.get("/performance/report", response_model=PerformanceReportSchema)
def performance_full_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Relatório completo de performance (L1): bot response time, handoff rate, peak hours, status distribution. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_performance_report(start, end)
@router.get("/performance/report/export/pdf")
def performance_report_export_pdf(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Exporta relatório de performance em PDF."""
    start, end = parse_dates(start_date, end_date, period)
    report_data = svc.get_performance_report(start, end)
    
    # Gerar PDF
    pdf_bytes = ExportService.export_performance_report_pdf(report_data)
    
    # Filename com timestamp
    filename = f"performance_report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
    
    return Response(
        content=pdf_bytes,
        media_type="application/pdf",
        headers={"Content-Disposition": f"attachment; filename={filename}"}
    )
@router.get("/performance/report/export/excel")
def performance_report_export_excel(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Exporta relatório de performance em Excel."""
    start, end = parse_dates(start_date, end_date, period)
    report_data = svc.get_performance_report(start, end)
    
    # Gerar Excel
    excel_bytes = ExportService.export_performance_report_excel(report_data)
    
    # Filename com timestamp
    filename = f"performance_report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.xlsx"
    
    return Response(
        content=excel_bytes,
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={"Content-Disposition": f"attachment; filename={filename}"}
    )
# =============================================================================
# CONVERSION REPORTS EXTENDED (Sprint 12 - L2)
# =============================================================================

@router.get("/conversion/time-to-conversion-extended", response_model=TimeToConversionExtendedResponse)
def time_to_conversion_extended_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Tempo até conversão com p75, p90. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_time_to_conversion_extended(start, end)
@router.get("/conversion/by-source", response_model=ConversionBySourceResponse)
def conversion_by_source_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Taxa de conversão por origem (direct, group). Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_conversion_by_source(start, end)
@router.get("/conversion/lost-leads", response_model=LostLeadsAnalysisResponse)
def lost_leads_analysis_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Análise de leads perdidos (status LOST). Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_lost_leads_analysis(start, end)
@router.get("/conversion/trend", response_model=ConversionTrendResponse)
def conversion_trend_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    granularity: str = Query("day", pattern="^(day|week|month)$"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Tendência temporal de conversão (day/week/month). Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_conversion_trend(start, end, granularity)
@router.get("/conversion/report-extended", response_model=ConversionReportExtendedSchema)
def conversion_report_extended(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Relatório COMPLETO de conversão (L2): time to conversion extended, by source, lost leads, trend. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_conversion_report_extended(start, end)


# =====================================================================
# L3: CONVERSATION ANALYSIS ENDPOINTS
# =====================================================================

@router.get("/conversation/activity-heatmap", response_model=dict)
def conversation_activity_heatmap(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Heatmap de atividade: mensagens por dia da semana e hora. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_activity_heatmap(start, end)


@router.get("/conversation/keywords", response_model=dict)
def conversation_keywords(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    limit: int = Query(50, ge=1, le=200),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Palavras-chave mais frequentes nas mensagens INBOUND. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_keyword_frequency(start, end, limit)


@router.get("/conversation/sentiment", response_model=dict)
def conversation_sentiment(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Análise de sentimento nas mensagens INBOUND. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_sentiment_distribution(start, end)


@router.get("/conversation/topics", response_model=dict)
def conversation_topics(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Topics mais discutidos nas mensagens INBOUND. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_topic_distribution(start, end)


@router.get("/conversation/report", response_model=ConversationAnalysisReportSchema)
def conversation_analysis_report(
    start_date: str | None = None,
    end_date: str | None = None,
    period: str = Query("30d"),
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Relatório COMPLETO de análise de conversas (L3): heatmap, keywords, sentiment, topics. Cache 15min."""
    start, end = parse_dates(start_date, end_date, period)
    return svc.get_conversation_analysis_report(start, end)

# =====================================================================
# L4: REAL-TIME DASHBOARD ENDPOINTS
# =====================================================================

@router.get("/realtime/dashboard", response_model=RealtimeDashboardSchema)
def realtime_dashboard(
    current_user: UserModel = Depends(get_current_user),
    svc: MetricsService = Depends(get_metrics_service),
):
    """Dashboard completo em tempo real: summary, active conversations, queue stats, alerts. Cache 30s."""
    return svc.get_realtime_dashboard()


@router.websocket("/ws/realtime")
async def websocket_realtime_metrics(
    websocket: WebSocket,
    svc: MetricsService = Depends(get_metrics_service),
):
    """
    WebSocket para streaming de métricas em tempo real.
    
    Envia atualizações a cada 5 segundos com:
    - Conversas ativas
    - Sumário de métricas
    - Queue stats
    - Alertas de performance
    """
    import asyncio
    import json
    
    await websocket.accept()
    
    try:
        while True:
            # Buscar métricas sem cache (sempre fresh)
            data = svc.get_realtime_dashboard()
            
            # Enviar para cliente
            await websocket.send_text(json.dumps(data))
            
            # Aguardar 5 segundos antes de próximo update
            await asyncio.sleep(5)
            
    except WebSocketDisconnect:
        pass
    except Exception as e:
        await websocket.close(code=1011, reason=str(e))
