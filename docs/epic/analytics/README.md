# Epic: Analytics (Métricas & Cache)

**Status:** IMPLEMENTADO  
**Implementation:** 1284 linhas (metrics_service.py)
**Owner:** Backend & Product Team
**Last Updated:** Janeiro 2026

---

## O Que Existe (Código Real)

### Core Architecture

**MetricsService** implementa cache-aside pattern com Redis:
- `_generate_cache_key()`: Chaves determinísticas com params normalizados
- `_get_cached_or_compute()`: Cache-aside pattern (miss → compute → store)
- `_invalidate_cache_pattern()`: Scan + delete para invalidação

**TTLs (de settings):**
- REALTIME: 30 segundos (5 minutos por defaut)
- METRICS: 15 minutos (dashboards)
- HISTORICAL: 1 hora (relatórios)
- REPORTS: 30 minutos (aggregations)

**Arquivo:** metrics_service.py:1-1284

---

### 1. Dashboard

#### get_dashboard_summary()
- **TTL:** REALTIME (5 min)
- **Retorna:** KPIs executivos (total_leads, converted_leads, conversion_rate%, avg_response_time, total_conversations, active_conversations, total_messages, avg_messages_per_conversation)
- **Arquivo:** metrics_service.py:146-182

---

### 2. Conversion Analytics

#### get_conversion_rate()
- **TTL:** METRICS (15 min)
- **Input:** start_date, end_date, segment_by (optional)
- **Retorna:** {total_leads, converted_leads, conversion_rate%} ± segments se segment_by
- **Arquivo:** metrics_service.py:191-221

#### get_conversion_funnel()
- **TTL:** METRICS (15 min)
- **Retorna:** Stages com count, percentage, drop_off
- **Arquivo:** metrics_service.py:223-260

#### get_time_to_conversion()
- **TTL:** METRICS (15 min)
- **Retorna:** {avg_hours, median_hours, min_hours, max_hours, p95_hours}
- **Arquivo:** metrics_service.py:262-299

#### get_conversion_by_source()
- **TTL:** METRICS (15 min)
- **Retorna:** [{source, total_leads, converted_leads, conversion_rate%}]
- **Arquivo:** metrics_service.py:902-944

#### get_conversion_trend()
- **TTL:** METRICS (15 min)
- **Input:** granularity (day, week, month)
- **Retorna:** [{period, total_leads, converted_leads, conversion_rate%}]
- **Arquivo:** metrics_service.py:1001-1047

#### get_lost_leads_analysis()
- **TTL:** METRICS (15 min)
- **Retorna:** {total_lost, lost_by_maturity_range, avg_time_before_lost_hours}
- **Arquivo:** metrics_service.py:946-999

#### get_time_to_conversion_extended()
- **TTL:** METRICS (15 min)
- **Retorna:** {avg_hours, median, p75, p90, p95, min, max}
- **Arquivo:** metrics_service.py:851-900

#### get_conversion_report_extended()
- **TTL:** REPORTS (30 min, agregado)
- **Retorna:** Combinação de time_to_conversion + by_source + lost_leads + trend_daily
- **Arquivo:** metrics_service.py:1049-1082

---

### 3. Performance Analytics

#### get_response_time_stats()
- **TTL:** REALTIME (5 min)
- **Input:** user_id (optional)
- **Retorna:** {avg_seconds, median_seconds, p95_seconds, p99_seconds, total_responses}
- **Arquivo:** metrics_service.py:319-361

#### get_message_volume()
- **TTL:** METRICS (15 min)
- **Input:** granularity (day, week)
- **Retorna:** [{timestamp, incoming, outgoing, total}]
- **Arquivo:** metrics_service.py:363-405

#### get_bot_response_time()
- **TTL:** METRICS (15 min)
- **Retorna:** {avg_ms, median_ms, p95_ms, p99_ms, min_ms, max_ms, total_interactions}
- **Arquivo:** metrics_service.py:510-553

#### get_peak_hours()
- **TTL:** METRICS (15 min)
- **Retorna:** [{hour, message_count, conversation_count}]
- **Arquivo:** metrics_service.py:778-824

#### get_performance_report()
- **TTL:** REPORTS (30 min, agregado)
- **Retorna:** Combinação de bot_response_time + handoff_stats + peak_hours + status_distribution
- **Arquivo:** metrics_service.py:555-610

---

### 4. Bot Performance

#### get_bot_autonomy_rate()
- **TTL:** METRICS (15 min)
- **Retorna:** {total_conversations, bot_only, with_handoff, autonomy_rate%}
- **Arquivo:** metrics_service.py:440-479

#### get_handoff_rate()
- **TTL:** METRICS (15 min)
- **Retorna:** {total_conversations, bot_resolved, handoff_required, handoff_rate%, auto_resolution_rate%}
- **Arquivo:** metrics_service.py:612-659

#### get_conversations_by_status()
- **TTL:** METRICS (15 min)
- **Retorna:** [{status, count, percentage}]
- **Arquivo:** metrics_service.py:746-776

---

### 5. L3: Conversation Analysis

#### get_activity_heatmap()
- **TTL:** METRICS (15 min)
- **Retorna:** [{day_of_week, hour, message_count}]
- **Arquivo:** metrics_service.py:1084-1120

---

## Cache Strategy

**Pattern:** Cache-aside
1. `_get_cached_or_compute(key, ttl, fn, *args)`
2. Try Redis GET → return json.loads()
3. Cache MISS → call fn(*args) → json.dumps() + SETEX
4. Redis errors: fallback to compute (graceful degradation)

**Cache Key Format:**
```
metrics:{metric_name}:{period}:{user_part}:{params_hash}
Example: metrics:conversion_rate:2026-01-01_2026-01-31:global:segment_by=source
```

---

## Integração com Outros Epics

- **AnalyticsRepository:** Dados brutos via queries
- **Settings:** TTLs configuráveis por environment
- **Redis:** Cache backend
- **Handoff:** Métricas de handoff em mark_as_completed()

---

## Gaps / Limitações

1. **Sem forecasting** - forecast_service.py existe mas NÃO está integrado em MetricsService
2. **Sem sentimento** - Análise não implementada
3. **Sem WebSocket real-time** - Dashboard estático apenas
4. **Sem export** - CSV/Excel/PDF não existe
5. **Sem audit de queries** - Sem log quem acessou métricas
6. **JSON simples** - Sem compressão de payloads grandes

### Handoff Metrics
Query conversations where status = TRANSFERRED
  → Average duration (transferred_at - escalated_at)
  → Count by escalation_reason
  → Group by assigned_user_id (agent performance)
  → Display in agent dashboard

## Testing

**Unit Tests (TBD):**
- `test_conversion_rate_calculation`: Verify accuracy
- `test_lead_forecast_accuracy`: Compare prediction vs actual
- `test_metrics_cache_invalidation`: Verify refresh logic
- `test_source_attribution`: Verify lead source tracking

## Next Steps (High Priority)

1. **Implement real-time dashboard** (WebSocket for live updates)
2. **Add funnel analysis** (stage-by-stage conversion tracking)
3. **Build custom report builder** (user-defined metrics)
4. **Add cohort analysis** (segment users by signup date, source, etc)
5. **Sentiment analysis** (patient satisfaction from messages)
6. **Export to BI tools** (Tableau, Power BI integration)

## Real-time Dashboard Features (Frontend Integration)

- Active conversations count (live updates)
- Recent conversions (as they happen)
- Conversion rate (rolling 24h)
- Average response time (current)
- Waiting patients (pending handoff)
- Agent status (online/offline, load)
- System health (queue depth, API latency)
