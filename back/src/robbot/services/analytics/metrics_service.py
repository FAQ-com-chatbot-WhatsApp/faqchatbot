"""
Metrics Service

Service de alto nível para cálculo e cache de métricas.
Implementa caching inteligente no Redis com TTL configurável.
"""

import json
import logging
from datetime import datetime
from typing import Any
from uuid import UUID

from redis import Redis

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

logger = logging.getLogger(__name__)

# Service for business metrics with Redis caching
class MetricsService:
    """
    Service para cálculo e cache de métricas de negócio.
    
    Estratégia de cache:
    - Dashboard summary: TTL 5min (atualização rápida)
    - Conversion metrics: TTL 15min (menos voláteis)
    - Historical data: TTL 1h (dados históricos mudam pouco)
    
    Cache key pattern: metrics:{metric_name}:{period}:{user_id}:{hash_params}
    """

    CACHE_TTL_REALTIME = 300  # 5 minutos
    CACHE_TTL_METRICS = 900  # 15 minutos
    CACHE_TTL_HISTORICAL = 3600  # 1 hora

    def __init__(
        self,
        conversion_repo: ConversionAnalyticsRepository,
        performance_repo: PerformanceAnalyticsRepository,
        bot_performance_repo: BotPerformanceAnalyticsRepository,
        dashboard_repo: DashboardAnalyticsRepository,
        redis_client: Redis,
    ):
        self.conversion = conversion_repo
        self.performance = performance_repo
        self.bot_performance = bot_performance_repo
        self.dashboard = dashboard_repo
        self.redis = redis_client

    # =============================================================================
    # UTILITY METHODS
    # =============================================================================

    def _generate_cache_key(
        self,
        metric_name: str,
        start_date: datetime,
        end_date: datetime,
        user_id: UUID | None = None,
        **kwargs,
    ) -> str:
        """Gera chave de cache consistente"""
        period = f"{start_date.date()}_{end_date.date()}"
        user_part = f"user_{user_id}" if user_id else "global"

        # Hash dos parâmetros extras (sorted para consistência)
        if kwargs:
            params_str = "_".join(f"{k}={v}" for k, v in sorted(kwargs.items()))
            return f"metrics:{metric_name}:{period}:{user_part}:{params_str}"

        return f"metrics:{metric_name}:{period}:{user_part}"

    def _get_cached_or_compute(
        self,
        cache_key: str,
        ttl: int,
        compute_fn,
        *args,
        **kwargs,
    ) -> Any:
        """
        Pattern: Cache-aside
        
        1. Tenta buscar no cache
        2. Se cache miss, computa e salva
        3. Retorna resultado
        """
        try:
            # Tentar buscar do cache
            cached = self.redis.get(cache_key)
            if cached:
                logger.debug("Cache HIT: %s", cache_key)
                return json.loads(cached)

            logger.debug("Cache MISS: %s", cache_key)
        except Exception as e:  # noqa: BLE001
            logger.warning("Redis error on GET %s: %s", cache_key, e)
            # Continua sem cache se Redis falhar

        # Computar métrica
        result = compute_fn(*args, **kwargs)

        # Salvar no cache
        try:
            self.redis.setex(
                cache_key,
                ttl,
                json.dumps(result, default=str)  # default=str para datetime
            )
            logger.debug("Cache SET: %s (TTL=%ss)", cache_key, ttl)
        except Exception as e:  # noqa: BLE001
            logger.warning("Redis error on SET %s: %s", cache_key, e)

        return result

    def _invalidate_cache_pattern(self, pattern: str):
        """Invalida todas as chaves que batem com o pattern"""
        try:
            # Scan para evitar KEYS (blocking)
            keys_to_delete = []
            for key in self.redis.scan_iter(match=pattern):
                keys_to_delete.append(key)

            if keys_to_delete:
                self.redis.delete(*keys_to_delete)
                logger.info("Invalidated %s cache keys matching %s", len(keys_to_delete), pattern)
        except Exception as e:  # noqa: BLE001
            logger.error("Failed to invalidate cache pattern %s: %s", pattern, e)

    # =============================================================================
    # DASHBOARD METRICS
    # =============================================================================

    def get_dashboard_summary(
        self,
        start_date: datetime,
        end_date: datetime,
    ) -> dict[str, Any]:
        """
        Resumo executivo do dashboard.
        
        Cache: 5 minutos (dados em tempo real)
        
        Returns:
            {
                "period": {"start": "2024-12-01", "end": "2024-12-31"},
                "kpis": {
                    "total_leads": 150,
                    "converted_leads": 45,
                    "conversion_rate": 30.0,
                    "avg_response_time_seconds": 180.5,
                    "total_conversations": 200,
                    "active_conversations": 25,
                    "total_messages": 3500,
                    "avg_messages_per_conversation": 17.5
                }
            }
        """
        cache_key = self._generate_cache_key(
            "dashboard_summary",
            start_date,
            end_date,
        )

        result = self._get_cached_or_compute(
            cache_key,
            self.CACHE_TTL_REALTIME,
            self.dashboard.get_dashboard_summary,
            start_date,
            end_date,
        )

        return {
            "period": {
                "start": start_date.date().isoformat(),
                "end": end_date.date().isoformat(),
            },
            "kpis": result,
        }

    # =============================================================================
    # CONVERSION ANALYTICS
    # =============================================================================

    def get_conversion_rate(
        self,
        start_date: datetime,
        end_date: datetime,
        segment_by: str | None = None,
    ) -> dict[str, Any]:
        """
        Taxa de conversão global ou segmentada.
        
        Cache: 15 minutos
        
        Returns:
            {
                "period": {...},
                "conversion": {
                    "total_leads": 150,
                    "converted_leads": 45,
                    "conversion_rate": 30.0
                },
                "segments": [...]  # se segmentado
            }
        """
        cache_key = self._generate_cache_key(
            "conversion_rate",
            start_date,
            end_date,
            segment_by=segment_by,
        )

        result = self._get_cached_or_compute(
            cache_key,
            self.CACHE_TTL_METRICS,
            self.conversion.get_conversion_rate,
            start_date,
            end_date,
            segment_by,
        )

        return {
            "period": {
                "start": start_date.date().isoformat(),
                "end": end_date.date().isoformat(),
            },
            "conversion": result if not segment_by else None,
            "segments": result.get("segments") if segment_by else None,
        }

    def get_conversion_funnel(
        self,
        start_date: datetime,
        end_date: datetime,
    ) -> dict[str, Any]:
        """
        Análise de funil de conversão com drop-off.
        
        Cache: 15 minutos
        
        Returns:
            {
                "period": {...},
                "funnel": {
                    "stages": [
                        {
                            "stage": "created",
                            "name": "Leads Criados",
                            "count": 150,
                            "percentage": 100.0,
                            "drop_off": 0.0
                        },
                        ...
                    ]
                }
            }
        """
        cache_key = self._generate_cache_key(
            "conversion_funnel",
            start_date,
            end_date,
        )

        result = self._get_cached_or_compute(
            cache_key,
            self.CACHE_TTL_METRICS,
            self.conversion.get_conversion_funnel,
            start_date,
            end_date,
        )

        return {
            "period": {
                "start": start_date.date().isoformat(),
                "end": end_date.date().isoformat(),
            },
            "funnel": result,
        }

    def get_time_to_conversion(
        self,
        start_date: datetime,
        end_date: datetime,
    ) -> dict[str, Any]:
        """
        Estatísticas de tempo até conversão.
        
        Cache: 15 minutos
        
        Returns:
            {
                "period": {...},
                "time_stats": {
                    "avg_hours": 48.5,
                    "median_hours": 36.0,
                    "min_hours": 2.0,
                    "max_hours": 168.0,
                    "p95_hours": 120.0
                }
            }
        """
        cache_key = self._generate_cache_key(
            "time_to_conversion",
            start_date,
            end_date,
        )

        result = self._get_cached_or_compute(
            cache_key,
            self.CACHE_TTL_METRICS,
            self.conversion.get_time_to_conversion,
            start_date,
            end_date,
        )

        return {
            "period": {
                "start": start_date.date().isoformat(),
                "end": end_date.date().isoformat(),
            },
            "time_stats": result,
        }

    # =============================================================================
    # PERFORMANCE ANALYTICS
    # =============================================================================

    def get_response_time_stats(
        self,
        start_date: datetime,
        end_date: datetime,
        user_id: UUID | None = None,
    ) -> dict[str, Any]:
        """
        Estatísticas de tempo de resposta.
        
        Cache: 5 minutos (dados operacionais)
        
        Returns:
            {
                "period": {...},
                "user_id": "uuid" | null,
                "response_time": {
                    "avg_seconds": 180.5,
                    "median_seconds": 120.0,
                    "p95_seconds": 600.0,
                    "p99_seconds": 1200.0,
                    "total_responses": 350
                }
            }
        """
        cache_key = self._generate_cache_key(
            "response_time_stats",
            start_date,
            end_date,
            user_id=user_id,
        )

        result = self._get_cached_or_compute(
            cache_key,
            self.CACHE_TTL_REALTIME,
            self.performance.get_response_time_stats,
            start_date,
            end_date,
            user_id,
        )

        return {
            "period": {
                "start": start_date.date().isoformat(),
                "end": end_date.date().isoformat(),
            },
            "user_id": str(user_id) if user_id else None,
            "response_time": result,
        }

    def get_message_volume(
        self,
        start_date: datetime,
        end_date: datetime,
        granularity: str = "day",
    ) -> dict[str, Any]:
        """
        Volume de mensagens ao longo do tempo.
        
        Cache: 15 minutos
        
        Returns:
            {
                "period": {...},
                "granularity": "day",
                "data_points": [
                    {
                        "timestamp": "2024-12-18T00:00:00",
                        "incoming": 150,
                        "outgoing": 180,
                        "total": 330
                    },
                    ...
                ]
            }
        """
        cache_key = self._generate_cache_key(
            "message_volume",
            start_date,
            end_date,
            granularity=granularity,
        )

        result = self._get_cached_or_compute(
            cache_key,
            self.CACHE_TTL_METRICS,
            self.performance.get_message_volume,
            start_date,
            end_date,
            granularity,
        )

        return {
            "period": {
                "start": start_date.date().isoformat(),
                "end": end_date.date().isoformat(),
            },
            "granularity": granularity,
            "data_points": result,
        }

    # =============================================================================
    # BOT PERFORMANCE
    # =============================================================================

    def get_bot_autonomy_rate(
        self,
        start_date: datetime,
        end_date: datetime,
    ) -> dict[str, Any]:
        """
        Taxa de autonomia do bot.
        
        Cache: 15 minutos
        
        Returns:
            {
                "period": {...},
                "autonomy": {
                    "total_conversations": 200,
                    "bot_only": 120,
                    "with_handoff": 80,
                    "autonomy_rate": 60.0
                }
            }
        """
        cache_key = self._generate_cache_key(
            "bot_autonomy_rate",
            start_date,
            end_date,
        )

        result = self._get_cached_or_compute(
            cache_key,
            self.CACHE_TTL_METRICS,
            self.bot_performance.get_bot_autonomy_rate,
            start_date,
            end_date,
        )

        return {
            "period": {
                "start": start_date.date().isoformat(),
                "end": end_date.date().isoformat(),
            },
            "autonomy": result,
        }

    # =============================================================================
    # CACHE MANAGEMENT
    # =============================================================================

    def invalidate_all_metrics(self):
        """Invalida todo o cache de métricas (usar com cautela)"""
        self._invalidate_cache_pattern("metrics:*")

    def invalidate_metric(self, metric_name: str):
        """Invalida cache de uma métrica específica"""
        self._invalidate_cache_pattern(f"metrics:{metric_name}:*")

    def get_cache_stats(self) -> dict[str, Any]:
        """Estatísticas do cache Redis"""
        try:
            info = self.redis.info("stats")
            return {
                "keyspace_hits": info.get("keyspace_hits", 0),
                "keyspace_misses": info.get("keyspace_misses", 0),
                "hit_rate": round(
                    info.get("keyspace_hits", 0) /
                    (info.get("keyspace_hits", 0) + info.get("keyspace_misses", 1)) * 100,
                    2
                ),
            }
        except Exception as e:  # noqa: BLE001
            logger.error("Failed to get cache stats: %s", e)
            return {}
