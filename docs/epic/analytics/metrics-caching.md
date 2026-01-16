# Analytics Metrics & Caching Layer

## Purpose
Wrap analytics computations with cache-aside logic, consistent cache keys, and tiered TTLs for conversion/performance dashboards.

## Cache Strategy
- `_generate_cache_key(metric_name, start_date, end_date, **kwargs)`: builds deterministic keys including filters.
- `_get_cached_or_compute(cache_key, ttl_seconds, compute_fn, *args)`: attempts Redis read; on miss computes via `compute_fn`, stores serialized result with TTL.
- TTLs from settings: `ANALYTICS_CACHE_TTL_DASHBOARD` (dashboard summary), `ANALYTICS_CACHE_TTL_METRICS` (15m typical), `ANALYTICS_CACHE_TTL_REALTIME` (5m for near-real-time stats).

## Metrics Covered (samples)
- Conversion: `get_conversion_summary`, `get_conversion_rate`, `get_conversion_funnel`, `get_time_to_conversion` (all cached with metric TTL, return period metadata).
- Performance: `get_response_time_stats(user_id=None)`, `get_message_volume(granularity)`, `get_bot_autonomy_rate`, `get_bot_response_time` (bot latency). Each caches per metric with appropriate TTL.
- Dashboard: `get_dashboard_summary(start_date, end_date)`: cached with dashboard TTL.

## Behavior / Notes
- Returns include period start/end ISO strings; segmenting handled in analytics adapter; cache stores full result blob.
- Cache failures fall back to compute path (no circuit-breaker); ensure Redis availability for optimal latency.
- When underlying data mutates, callers must decide whether to invalidate keys or accept TTL freshness.
