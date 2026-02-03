"""Job para polling de mensagens do WAHA (substitui webhooks não-funcionais do WEBJS)."""

import logging
from datetime import datetime, timedelta, timezone

import httpx
from rq import get_current_job

from robbot.config.settings import get_settings
from robbot.infra.db.session import get_sync_session
from robbot.infra.redis.client import get_redis_client
from robbot.services.queue_service import get_queue_service

logger = logging.getLogger(__name__)
settings = get_settings()


def poll_waha_messages(**kwargs):
    """
    Busca mensagens novas do WAHA via API e enfileira para processamento.
    
    FALLBACK DE WEBHOOKS:
    - Priorize webhooks (message/message.any) para capturar todas as mensagens
    - Polling periódico via GET /api/{session}/chats como fallback
    - Para contatos @c.us, tenta resolver LID via GET /api/{session}/lids/pn/{phoneNumber}
    
    DEV_MODE:
    - True: Processa APENAS mensagens de DEV_PHONE_NUMBER
    - False: Processa mensagens de TODOS os números
    
    Args:
        **kwargs: Aceita argumentos adicionais do RQ (ex: timeout) mas não os utiliza
    """
    job = get_current_job()
    job_id = job.id if job else "no-job"
    
    logger.info(
        "[POLLING] Iniciando busca de mensagens no WAHA",
        extra={"job_id": job_id, "dev_mode": settings.DEV_MODE}
    )
    
    with get_sync_session() as session:
        queue_service = get_queue_service()
        redis_client = get_redis_client()
        
        try:
            headers = {"X-Api-Key": settings.WAHA_API_KEY}
            messages_processed = 0
            messages_skipped = 0

            with httpx.Client(timeout=30.0) as client:
                chat_ids: list[str] = []
                if settings.DEV_MODE and settings.DEV_PHONE_NUMBER:
                    target_phone = settings.DEV_PHONE_NUMBER
                    normalized_phone = target_phone
                    check_exists_url = (
                        f"{settings.WAHA_URL}/api/contacts/check-exists"
                        f"?session={settings.WAHA_SESSION_NAME}&phone={target_phone}"
                    )
                    check_exists_response = client.get(check_exists_url, headers=headers)
                    if check_exists_response.status_code == 200:
                        check_payload = check_exists_response.json()
                        normalized_chat_id = check_payload.get("chatId")
                        if normalized_chat_id:
                            normalized_phone = normalized_chat_id.split("@")[0]

                    lids_url = f"{settings.WAHA_URL}/api/{settings.WAHA_SESSION_NAME}/lids/pn/{normalized_phone}"
                    lids_response = client.get(lids_url, headers=headers)
                    if lids_response.status_code == 200:
                        lid_payload = lids_response.json()
                        resolved_lid = lid_payload.get("lid")
                        if resolved_lid:
                            chat_ids = [resolved_lid]
                    if not chat_ids:
                        logger.warning(
                            "[POLLING] LID não encontrado para DEV_PHONE_NUMBER=%s (normalizado=%s); aguardando sincronização",
                            target_phone,
                            normalized_phone,
                        )
                else:
                    overview_url = f"{settings.WAHA_URL}/api/{settings.WAHA_SESSION_NAME}/chats/overview"
                    overview_params = {"limit": 200, "offset": 0}
                    overview_response = client.get(overview_url, headers=headers, params=overview_params)
                    if overview_response.status_code == 200:
                        try:
                            chat_ids = [chat.get("id") for chat in overview_response.json() if chat.get("id")]
                        except Exception:
                            chat_ids = []
                    else:
                        logger.warning(
                            "[POLLING] Falha ao buscar chats/overview: %s",
                            overview_response.text,
                            extra={"status": overview_response.status_code},
                        )

                    if not chat_ids:
                        chats_url = f"{settings.WAHA_URL}/api/{settings.WAHA_SESSION_NAME}/chats"
                        chats_params = {"limit": 200, "offset": 0}
                        chats_response = client.get(chats_url, headers=headers, params=chats_params)
                        if chats_response.status_code == 200:
                            try:
                                chat_ids = [chat.get("id") for chat in chats_response.json() if chat.get("id")]
                            except Exception:
                                chat_ids = []
                        else:
                            logger.warning(
                                "[POLLING] Falha ao buscar chats: %s",
                                chats_response.text,
                                extra={"status": chats_response.status_code},
                            )

                if not chat_ids:
                    logger.warning("[POLLING] Sem chats para processar")
                    return

                # Processar cada chat
                for chat_id in chat_ids:
                    resolved_chat_id = chat_id
                    if chat_id.endswith("@c.us"):
                        phone = chat_id.split("@")[0]
                        lids_url = f"{settings.WAHA_URL}/api/{settings.WAHA_SESSION_NAME}/lids/pn/{phone}"
                        lids_response = client.get(lids_url, headers=headers)
                        if lids_response.status_code == 200:
                            lid_payload = lids_response.json()
                            resolved_chat_id = lid_payload.get("lid")
                            if not resolved_chat_id:
                                logger.debug(
                                    "[POLLING] LID vazio para %s; ignorando chat",
                                    chat_id,
                                    extra={"chat_id": chat_id},
                                )
                                continue
                        else:
                            logger.debug(
                                "[POLLING] LID não encontrado para %s (status=%s)",
                                chat_id,
                                lids_response.status_code,
                                extra={"chat_id": chat_id},
                            )
                            continue

                    messages_url = (
                        f"{settings.WAHA_URL}/api/{settings.WAHA_SESSION_NAME}/chats/"
                        f"{resolved_chat_id}/messages"
                    )
                    params = {"limit": 10}

                    msg_response = client.get(messages_url, headers=headers, params=params)

                    if msg_response.status_code == 422:
                        logger.warning(
                            "[POLLING] WAHA retornou 422 (sessão não pronta ou payload inválido): %s",
                            msg_response.text,
                            extra={"status": msg_response.status_code, "chat_id": chat_id},
                        )
                        continue

                    if msg_response.status_code >= 500:
                        logger.error(
                            "[POLLING] WAHA retornou %s ao buscar mensagens: %s",
                            msg_response.status_code,
                            msg_response.text,
                            extra={"status": msg_response.status_code, "chat_id": chat_id},
                        )
                        continue

                    msg_response.raise_for_status()
                    messages = msg_response.json()

                    # Processar apenas mensagens recebidas (não enviadas pelo bot)
                    for message in messages:
                        if message.get("fromMe", True):  # Ignorar mensagens enviadas pelo bot
                            continue

                        # Verificar timestamp (processar mensagens recentes; confiar no dedupe por message_id)
                        timestamp = message.get("timestamp", 0)
                        message_time = datetime.fromtimestamp(timestamp, tz=timezone.utc)
                        now = datetime.now(timezone.utc)

                        ack = message.get("ack", 0)
                        message_id = message.get("id")
                        if message_id:
                            redis_key = f"waha:processed:{message_id}"
                            if redis_client.get(redis_key):
                                messages_skipped += 1
                                continue

                        # Montar payload no formato esperado pelo webhook_controller
                        message_data = {
                            "id": message_id,
                            "from": message.get("from"),
                            "to": message.get("to"),
                            "body": message.get("body", ""),
                            "timestamp": timestamp,
                            "hasMedia": message.get("hasMedia", False),
                            "ack": ack,
                            "_data": message.get("_data", {}),
                        }

                        # Enfileirar para processamento
                        job_id = queue_service.enqueue_message_processing(
                            message_data=message_data,
                            message_direction="inbound",
                        )

                        if message_id:
                            redis_client.set(redis_key, "1", ex=86400)

                        messages_processed += 1

                        logger.info(
                            "[POLLING] Mensagem enfileirada: %s de %s",
                            message.get("id"),
                            message.get("from"),
                            extra={
                                "job_id": job_id,
                                "chat_id": message.get("from"),
                                "message_id": message.get("id"),
                                "timestamp": timestamp,
                            }
                        )
            
            logger.info(
                "[POLLING] Busca concluída - %d mensagens processadas, %d ignoradas",
                messages_processed,
                messages_skipped,
                extra={
                    "processed": messages_processed,
                    "skipped": messages_skipped,
                    "dev_mode": settings.DEV_MODE,
                }
            )
            
            return {
                "status": "success",
                "messages_processed": messages_processed,
                "messages_skipped": messages_skipped,
            }
                
        except httpx.HTTPError as e:
            logger.error(
                "[POLLING] Erro HTTP ao buscar mensagens: %s",
                e,
                extra={"error": str(e)},
                exc_info=True,
            )
            return {
                "status": "error",
                "reason": "http_error",
                "error": str(e),
            }
        except Exception as e:
            logger.error(
                "[POLLING] Erro inesperado no polling: %s",
                e,
                extra={"error": str(e)},
                exc_info=True,
            )
            return {
                "status": "error",
                "reason": "unexpected_error",
                "error": str(e),
            }
