"""
Autoscaling automático de workers baseado na carga das filas.

Este script deve ser executado periodicamente (ex: a cada 2 minutos)
para ajustar automaticamente o número de workers.

Regras:
- Mínimo: 2 workers (sempre)
- Máximo: 5 workers
- Scale UP: quando jobs/worker > 5
- Scale DOWN: quando todos workers ociosos e fila vazia

Uso:
    python scripts/autoscale_workers.py

    # Via cron (a cada 2 minutos):
    */2 * * * * cd /path/to/project && python scripts/autoscale_workers.py >> /var/log/autoscale.log 2>&1
"""

import subprocess
import sys
from datetime import datetime

from robbot.services.worker_analytics_service import WorkerAnalyticsService


def execute_scaling(target_workers: int) -> bool:
    """Execute docker compose scale command."""
    try:
        cmd = ["docker", "compose", "up", "-d", "--scale", f"worker={target_workers}"]
        cwd = "/app/back" if sys.platform.startswith("linux") else None

        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=60,
            cwd=cwd,
        )

        if result.returncode == 0:
            print(f"[{datetime.now()}] SUCCESS: Scaled to {target_workers} workers")
            return True
        else:
            print(f"[{datetime.now()}] ERROR: {result.stderr}")
            return False

    except subprocess.TimeoutExpired:
        print(f"[{datetime.now()}] ERROR: Scaling command timed out")
        return False
    except Exception as e:
        print(f"[{datetime.now()}] ERROR: {e}")
        return False


def main():
    """Run autoscaling check and execute if needed."""
    print(f"\n[{datetime.now()}] Starting autoscaling check...")

    try:
        service = WorkerAnalyticsService()
        should_scale, target, reason = service.should_autoscale()

        analytics = service.get_analytics()
        current_workers = analytics["workers"]["total"]
        pending_jobs = analytics["summary"]["total_pending"]

        print(f"[{datetime.now()}] Current state:")
        print(f"  Workers: {current_workers}")
        print(f"  Pending jobs: {pending_jobs}")
        print(f"  Recommendation: {analytics['autoscaling']['action']}")
        print(f"  Reason: {reason}")

        if should_scale:
            print(f"[{datetime.now()}] Executing scaling: {current_workers} -> {target} workers")
            success = execute_scaling(target)

            if success:
                print(f"[{datetime.now()}] Autoscaling completed successfully")
                return 0
            else:
                print(f"[{datetime.now()}] Autoscaling failed")
                return 1
        else:
            print(f"[{datetime.now()}] No scaling needed - system stable")
            return 0

    except Exception as e:
        print(f"[{datetime.now()}] FATAL ERROR: {e}")
        return 1


if __name__ == "__main__":
    sys.exit(main())
