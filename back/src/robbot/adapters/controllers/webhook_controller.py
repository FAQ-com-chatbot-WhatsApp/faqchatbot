"""Webhook controller for WAHA events (NO JWT auth)."""

import logging

from fastapi import APIRouter, Depends, Request, status
from sqlalchemy.orm import Session

from robbot.adapters.repositories.webhook_log_repository import WebhookLogRepository
from robbot.api.v1.dependencies import get_db
from robbot.core.custom_exceptions import ExternalServiceError, QueueError
from robbot.schemas.waha import WebhookLogOut, WebhookPayload
from robbot.services.queue_service import get_queue_service

router = APIRouter()

logger = logging.getLogger(__name__)


def _get_webhook_repo(db: Session = Depends(get_db)) -> WebhookLogRepository:
    """Dependency to create WebhookLogRepository."""
    return WebhookLogRepository(db)


@router.post(
    "/waha",
    response_model=WebhookLogOut,
    status_code=status.HTTP_202_ACCEPTED,
)
async def receive_waha_webhook(
    payload: WebhookPayload,
    _request: Request,
    repo: WebhookLogRepository = Depends(_get_webhook_repo),
):
    """Receive webhook from WAHA.

    **No authentication** - Webhook interno entre containers Docker.
    Para produção, configure firewall/VPN para proteger este endpoint.

    This endpoint receives all WAHA events (messages, status, acks)
    and stores them in the database for async processing (Épico 3).

    Events:
    - `message` - Incoming message
    - `message.ack` - Message acknowledgment
    - `session.status` - Session status change
    """
    logger.info(
        "Webhook received: %s from session %s",
        payload.event,
        payload.session,
        extra={"event": payload.event, "session": payload.session},
    )

    log = repo.create(
        session_name=payload.session,
        event_type=payload.event,
        payload=payload.payload,
    )

    queue_service = get_queue_service()

    try:
        if payload.event == "message" and payload.payload:
            message_data = payload.payload

            chat_id = message_data.get("from", "")
            phone = chat_id.split("@")[0] if "@" in chat_id else chat_id

            job_id = queue_service.enqueue_message_processing(
                message_data=message_data,
                message_direction="inbound",
            )

            print(f"DEBUG: Job enqueued with ID: {job_id}")  # Debug print

            logger.info(
                "[SUCCESS] Mensagem enfileirada para processamento: %s",
                job_id,
                extra={
                    "job_id": job_id,
                    "phone": phone,
                    "chat_id": chat_id,
                    "webhook_log_id": log.id,
                },
            )
        else:
            logger.debug(
                "Evento '%s' registrado mas não enfileirado",
                payload.event,
                extra={"event": payload.event, "webhook_log_id": log.id},
            )

    except (QueueError, ExternalServiceError) as e:
        logger.error(
            "Erro ao enfileirar mensagem: %s",
            e,
            extra={"webhook_log_id": log.id, "error": str(e)},
            exc_info=True,
        )
    except Exception as e:  # noqa: BLE001 (blind exception)
        logger.error(
            "Erro inesperado ao processar webhook: %s",
            e,
            extra={"webhook_log_id": log.id, "error": str(e)},
            exc_info=True,
        )

    return WebhookLogOut.model_validate(log)


@router.get(
    "/waha/logs",
    response_model=list[WebhookLogOut],
)
async def get_webhook_logs(
    limit: int = 100,
    event_type: str | None = None,
    repo: WebhookLogRepository = Depends(_get_webhook_repo),
):
    """Get unprocessed webhook logs.

    **No authentication** - Endpoint interno para debugging.
    Para produção, proteja com JWT ou remova este endpoint.

    Useful for debugging and manual reprocessing.
    """
    logs = repo.get_unprocessed(limit=limit, event_type=event_type)
    return [WebhookLogOut.model_validate(log) for log in logs]
