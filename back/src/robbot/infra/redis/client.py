"""Redis connection factory with shared connection pool."""

from __future__ import annotations

import redis

from robbot.config.settings import settings

_pool: redis.ConnectionPool | None = None


def get_redis_pool() -> redis.ConnectionPool:
    """Return a singleton connection pool configured from settings."""
    global _pool
    if _pool is None:
        _pool = redis.ConnectionPool.from_url(
            settings.REDIS_URL,
            max_connections=settings.REDIS_MAX_CONNECTIONS,
            socket_connect_timeout=2,
            socket_timeout=2,
            decode_responses=False,
        )
    return _pool


def get_redis_client() -> redis.Redis:
    """Return a Redis client using the shared pool."""
    pool = get_redis_pool()
    return redis.Redis(connection_pool=pool)


def close_redis_pool() -> None:
    """Close the shared pool, freeing resources."""
    global _pool
    if _pool is not None:
        _pool.disconnect()
        _pool = None


def invalidate_analytics_cache() -> int:
    """Removes all dashboard and metrics keys from Redis."""
    try:
        from robbot.infra.redis.client import get_redis_client
        client = get_redis_client()
        # Invalidate dashboard, analytics and metrics keys
        keys = client.keys("dashboard:*") + client.keys("analytics:*") + client.keys("metrics:*")
        if keys:
            client.delete(*keys)
            return len(keys)
        return 0
    except Exception:
        return 0
