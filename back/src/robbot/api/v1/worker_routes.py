"""Worker analytics and management routes."""

import logging
import subprocess

from fastapi import APIRouter, Depends, HTTPException, status

from robbot.api.v1.dependencies import get_current_user
from robbot.schemas.worker import (
    AutoscalingConfig,
    ScaleWorkersRequest,
    WorkerAnalytics,
)
from robbot.services.worker_analytics_service import WorkerAnalyticsService

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/workers", tags=["Workers"])


@router.get("/analytics", response_model=WorkerAnalytics)
def get_worker_analytics(
    current_user=Depends(get_current_user),
):
    """Get worker analytics and queue statistics."""
    try:
        service = WorkerAnalyticsService()
        return service.get_analytics()
    except Exception as e:  # noqa: BLE001
        logger.error("Failed to get worker analytics: %s", e)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to retrieve worker analytics",
        )


@router.get("/autoscaling/config", response_model=AutoscalingConfig)
def get_autoscaling_config(
    current_user=Depends(get_current_user),
):
    """Get current autoscaling configuration."""
    service = WorkerAnalyticsService()
    return service.get_autoscaling_config()


@router.put("/autoscaling/config", response_model=AutoscalingConfig)
def update_autoscaling_config(
    config: AutoscalingConfig,
    current_user=Depends(get_current_user),
):
    """Update autoscaling configuration."""
    service = WorkerAnalyticsService()
    return service.update_autoscaling_config(config.model_dump())


@router.post("/scale")
def scale_workers(
    request: ScaleWorkersRequest,
    current_user=Depends(get_current_user),
):
    """Manually scale workers to target number.
    
    Note: This requires docker-compose access from the API container.
    For production, use Docker Swarm or Kubernetes.
    """
    try:
        # This is for development only
        # In production, use proper orchestration (K8s, Swarm, etc.)
        result = subprocess.run(
            ["docker", "compose", "up", "-d", "--scale", f"worker={request.target_workers}"],
            capture_output=True,
            text=True,
            timeout=30,
        )

        if result.returncode != 0:
            raise Exception(f"Docker command failed: {result.stderr}")

        return {
            "success": True,
            "target_workers": request.target_workers,
            "message": f"Scaling to {request.target_workers} workers",
        }
    except subprocess.TimeoutExpired:
        raise HTTPException(
            status_code=status.HTTP_504_GATEWAY_TIMEOUT,
            detail="Scaling operation timed out",
        )
    except Exception as e:  # noqa: BLE001
        logger.error("Failed to scale workers: %s", e)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to scale workers. This feature requires docker-compose access.",
        )
