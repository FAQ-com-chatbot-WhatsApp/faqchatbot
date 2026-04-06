
import asyncio
import os
import sys
from datetime import datetime, timedelta, UTC

# Adicionar o path do projeto
sys.path.append(os.getcwd() + "/src")

from robbot.infra.db.session import get_sync_session
from robbot.infra.persistence.models.conversation_message_model import ConversationMessageModel
from robbot.infra.persistence.models.conversation_model import ConversationModel
from robbot.domain.shared.enums import MessageDirection, ConversationStatus
from robbot.services.bot.conversation_orchestrator import get_conversation_orchestrator

async def reprocess_recent_messages(hours: int = 12):
    orchestrator = get_conversation_orchestrator()
    start_time = datetime.now(UTC) - timedelta(hours=hours)

    with get_sync_session() as session:
        # Pegar mensagens inbound recentes (últimas X horas)
        messages = session.query(ConversationMessageModel).join(ConversationModel).filter(
            ConversationMessageModel.direction == MessageDirection.INBOUND,
            ConversationMessageModel.created_at >= start_time
        ).order_by(ConversationMessageModel.created_at.asc()).all()

        print(f"Encontradas {len(messages)} mensagens INBOUND para reprocessar desde {start_time}.")

        for msg in messages:
            print(f"Reprocessando Mensagem ID={msg.id} de {msg.from_phone} | Corpo: {msg.body[:30]}...")
            
            try:
                # Obter a conversa
                conv = session.query(ConversationModel).filter(ConversationModel.id == msg.conversation_id).first()
                if not conv:
                    print(f"  [AVISO] Conversa {msg.conversation_id} não encontrada.")
                    continue

                # Reprocessar via Orchestrator (isso vai disparar toda a lógica de Score, Handoff e Resposta)
                result = await orchestrator.process_inbound_message(
                    chat_id=conv.chat_id,
                    phone_number=msg.from_phone,
                    message_text=msg.body,
                    session_name=conv.session_name or "default",
                    name=conv.lead_name or "Cliente",
                    has_audio=False,
                    audio_url=None
                )
                
                print(f"  [OK] Processada. Novo Status: {result.get('conversation_id')} | Intent: {result.get('intent')} | Score: {result.get('maturity_score')}")

            except Exception as e:
                print(f"  [ERRO] Falha ao reprocessar mensagem {msg.id}: {str(e)}")

if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument("--hours", type=int, default=12)
    args = parser.parse_args()
    
    asyncio.run(reprocess_recent_messages(args.hours))
