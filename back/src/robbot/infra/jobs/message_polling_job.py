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
    
    SUBSTITUIÇÃO DE WEBHOOKS:
    - WAHA WEBJS não dispara webhooks message/message.any de forma confiável
    - Solução: Polling periódico via GET /api/{session}/chats (doc oficial)
    
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
            # WEBJS: /chats está quebrado, usar endpoint direto
            # Em DEV_MODE: buscar apenas mensagens do DEV_PHONE_NUMBER
            phone_numbers = []
            if settings.DEV_MODE and settings.DEV_PHONE_NUMBER:
                phone_numbers = [settings.DEV_PHONE_NUMBER]
            
            if not phone_numbers:
                logger.warning("[POLLING] Sem números para processar")
                return
            
            headers = {"X-Api-Key": settings.WAHA_API_KEY}
            messages_processed = 0
            messages_skipped = 0
            
            # Processar cada número
            for phone in phone_numbers:
                chat_id = f"{phone}@c.us"
                
                # Buscar mensagens recentes deste chat (últimas 10)
                messages_url = f"{settings.WAHA_URL}/api/{settings.WAHA_SESSION_NAME}/chats/{chat_id}/messages"
                params = {"limit": 10}
                
                with httpx.Client(timeout=30.0) as client:
                    msg_response = client.get(messages_url, headers=headers, params=params)

                    if msg_response.status_code == 422:
                        logger.warning(
                            "[POLLING] WAHA retornou 422 (sessão não pronta ou payload inválido): %s",
                            msg_response.text,
                            extra={"status": msg_response.status_code},
                        )
                        return

                    msg_response.raise_for_status()
                    messages = msg_response.json()
                
                # Processar apenas mensagens recebidas (não enviadas pelo bot)
                for message in messages:
                    if message.get("fromMe", True):  # Ignorar mensagens enviadas pelo bot
                        continue
                    
                    # Verificar timestamp (processar apenas mensagens dos últimos 60 segundos)
                    timestamp = message.get("timestamp", 0)
                    message_time = datetime.fromtimestamp(timestamp, tz=timezone.utc)
                    now = datetime.now(timezone.utc)
                    
                    if now - message_time > timedelta(seconds=60):
                        continue
                    
                    # Verificar se já processamos esta mensagem (via ack ou outro indicador)
                    # WAHA retorna ack=1 (SERVER), 2 (DELIVERED), 3 (READ)
                    # Vamos processar apenas mensagens não lidas (ack < 3)
                    ack = message.get("ack", 0)
                    if ack >= 3:  # Já foi lida/processada
                        continue
                    
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
                        phone,
                        extra={
                            "job_id": job_id,
                            "phone": phone,
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
            raise
        except Exception as e:
            logger.error(
                "[POLLING] Erro inesperado no polling: %s",
                e,
                extra={"error": str(e)},
                exc_info=True,
            )
            raise
