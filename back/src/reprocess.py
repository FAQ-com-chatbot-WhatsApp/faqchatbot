
import asyncio
import os
import sys
from datetime import datetime, UTC

# Ensure app path
sys.path.append("/app/src")

from robbot.infra.db.session import get_sync_session
from robbot.infra.persistence.models.conversation_message_model import ConversationMessageModel
from robbot.infra.persistence.models.conversation_model import ConversationModel
from robbot.domain.shared.enums import MessageDirection
from robbot.services.bot.conversation_orchestrator import get_conversation_orchestrator

async def reprocess_all():
    orchestrator = get_conversation_orchestrator()
    print(f"[{datetime.now(UTC)}] Start reprocess all messages...")

    with get_sync_session() as session:
        # Get ALL inbound messages
        messages = session.query(ConversationMessageModel).order_by(ConversationMessageModel.created_at.asc()).all()
        inbound = [m for m in messages if m.direction == MessageDirection.INBOUND]
        print(f"Found {len(inbound)} INBOUND messages out of {len(messages)} total.")

        for msg in inbound:
            try:
                # Find associated conversation
                conv = session.query(ConversationModel).filter(ConversationModel.id == msg.conversation_id).first()
                if not conv:
                    print(f"  Skipping message {msg.id} (no conversation)")
                    continue

                print(f"  Reprocessing {msg.id} from {msg.from_phone} (Chat: {conv.chat_id})...")
                
                # Execute orchestrator pipeline
                # This will extract intent, update maturity score, and trigger handoff in lead table
                result = await orchestrator.process_inbound_message(
                    chat_id=conv.chat_id,
                    phone_number=msg.from_phone,
                    message_text=msg.body,
                    session_name=conv.session_name or "default",
                    name=conv.lead_name or "Cliente"
                )
                
                print(f"    [OK] Result: {result.get('intent')} | Score: {result.get('maturity_score')}")
                
            except Exception as e:
                print(f"    [ERROR] Failed for message {msg.id}: {str(e)}")

if __name__ == "__main__":
    asyncio.run(reprocess_all())
