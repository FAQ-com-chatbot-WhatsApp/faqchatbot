from datetime import UTC, datetime

from sqlalchemy.orm import Session

from robbot.adapters.external.waha_client import get_waha_client
from robbot.adapters.repositories.alert_repository import AlertRepository
from robbot.adapters.repositories.health_repository import HealthRepository
from robbot.adapters.repositories.session_repository import SessionRepository
from robbot.config.settings import settings
from robbot.schemas.health import HealthOut
from robbot.services.queue_service import get_queue_service


class HealthService:
    """
    Serviço responsável por verificar saúde da API e seus componentes (ex.: DB).
    """

    def __init__(self, db: Session):
        self.db = db
        self.health_repo = HealthRepository(db)
        self.alert_repo = AlertRepository(db)

    async def get_health(self) -> HealthOut:
        """
        Checa integrações essenciais (DB, Redis, WAHA, Queue) e retorna um resumo.
        Se houver falha crítica e alerts estiver configurado, persiste alerta.
        """
        now = datetime.now(UTC)
        db_ok = False
        db_error: str | None = None
        redis_ok = False
        redis_error: str | None = None
        waha_ok = False
        waha_error: str | None = None
        queue_ok = False
        queue_error: str | None = None
        active_sessions = 0

        try:
            db_ok = self.health_repo.ping()
        except Exception as exc:  # pragma: no cover - integration moment  # noqa: BLE001
            db_ok = False
            db_error = str(exc)

        try:
            redis_ok = self.health_repo.check_redis_connection()
        except Exception as exc:  # pragma: no cover - integration moment  # noqa: BLE001
            redis_ok = False
            redis_error = str(exc)

        # Check WAHA health
        try:
            waha_client = get_waha_client()
            await waha_client.ping()
            waha_ok = True
        except ExternalServiceError as exc:
            waha_ok = False
            waha_error = str(exc)
        except Exception as exc:  # noqa: BLE001
            waha_ok = False
            waha_error = str(exc)

        # Check Queue health
        try:
            queue_service = get_queue_service()
            queue_health = queue_service.health_check()
            queue_ok = queue_health.get("status") == "healthy"
        except Exception as exc:  # noqa: BLE001
            queue_ok = False
            queue_error = str(exc)

        # Count active sessions
        try:
            session_repo = SessionRepository(self.db)
            active_sessions = session_repo.count_active_sessions()
        except Exception:
            active_sessions = 0

        status_str = "ok" if db_ok and redis_ok and waha_ok and queue_ok else "unhealthy"

        # Se crítico (DB down) e alerts ativados, persistir alerta básico
        if not db_ok and getattr(settings, "SMTP_SENDER", None) is not None:
            # Persistir alerta para análise posterior
            try:
                self.alert_repo.create_alert(
                    level="critical",
                    message="Database unreachable in health check",
                    metadata={"error": db_error},
                )
            except Exception:
                # Não propagar erro de persistência de alerta no health check
                pass

        return HealthOut(
            status=status_str,
            components={
                "database": {"ok": db_ok, "error": db_error},
                "redis": {"ok": redis_ok, "error": redis_error},
                "waha": {"ok": waha_ok, "error": waha_error},
                "queue": {"ok": queue_ok, "error": queue_error},
            },
            active_sessions=active_sessions,
            timestamp=now,
        )
