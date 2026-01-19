
import asyncio
import logging
from robbot.infra.jobs.message_job import MessageProcessingJob

logging.basicConfig(level=logging.INFO)

# Dados que o webhook envia
payload = {
    "from": "5511999887766@c.us",
    "body": "Teste de depuração",
    "id": "debug_test_123",
    "timestamp": 1737242000,
    "type": "chat"
}

def run_debug():
    print("Iniciando Job de depuração...")
    job = MessageProcessingJob(
        message_data=payload,
        message_direction="inbound",
        attempt=1
    )
    
    # Chamar execute() diretamente para ver traceback real
    result = job.execute()
    print(f"Resultado: {result}")

if __name__ == "__main__":
    run_debug()
