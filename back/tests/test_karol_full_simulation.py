"""
Simulação completa da conversa da Karol
Valida: memória persistente, anti-repetição, handoff, extração de nome
"""
import time
import requests

WEBHOOK_URL = "http://localhost:3333/api/v1/webhooks/waha"
PHONE = "555198098876@c.us"

messages = [
    ("oi", 3, "Saudação inicial"),
    ("vi a clinica pelo instagram", 3, "Discovery channel"),
    ("me chamo karol", 3, "Nome revelado - deve salvar e usar"),
    ("quero saber sobre terapia hormonal", 3, "Interesse específico TRH"),
    ("ja fiz antes mas não deu certo", 3, "Experiência anterior - NÃO perguntar novamente 'já fez?'"),
    ("me senti mal com os efeitos", 3, "Problema específico"),
    ("queria algo mais natural", 3, "Necessidade específica"),
    ("quanto custa a consulta?", 3, "Pergunta sobre valor"),
    ("humm ta ok", 3, "Aceitação do valor"),
    ("tem disponibilidade essa semana?", 3, "**HANDOFF TRIGGER** - Calendário"),
    ("prefiro de tarde", 3, "Preferência de horário"),
    ("pode parcelar?", 3, "**HANDOFF TRIGGER** - Pagamento"),
    ("ok vou aguardar", 3, "Finalization"),
]

def send_message(text, wait_seconds):
    """Envia mensagem via webhook"""
    payload = {
        "event": "message",
        "session": "default",
        "payload": {
            "from": PHONE,
            "body": text,
            "timestamp": int(time.time())
        }
    }
    
    print(f"\n{'='*60}")
    print(f"📤 ENVIANDO: '{text}'")
    print(f"{'='*60}")
    
    response = requests.post(WEBHOOK_URL, json=payload)
    print(f"✅ Status: {response.status_code}")
    
    if response.status_code == 202:
        print(f"⏳ Aguardando {wait_seconds}s (debounce + processing)...")
        time.sleep(wait_seconds)
    else:
        print(f"❌ ERRO: {response.text}")
        return False
    
    return True

def main():
    """Executa simulação completa"""
    print("\n" + "="*60)
    print("🧪 SIMULAÇÃO CONVERSA KAROL")
    print("Objetivo: Validar memória persistente e handoff")
    print("="*60)
    
    for idx, (text, wait, note) in enumerate(messages, 1):
        print(f"\n📊 Mensagem {idx}/{len(messages)}: {note}")
        
        if not send_message(text, wait):
            print(f"\n❌ FALHA na mensagem {idx}. Abortando.")
            break
        
        if idx == 3:
            print("\n⚠️ CHECKPOINT 1: Nome 'Karol' deve ser extraído")
        elif idx == 5:
            print("\n⚠️ CHECKPOINT 2: Bot NÃO deve perguntar 'já fez antes?'")
        elif idx == 10:
            print("\n⚠️ CHECKPOINT 3: HANDOFF esperado (calendar_access)")
        elif idx == 12:
            print("\n⚠️ CHECKPOINT 4: HANDOFF esperado (payment_question)")
    
    print("\n" + "="*60)
    print("✅ SIMULAÇÃO COMPLETA")
    print("Agora analise os logs do worker para validar:")
    print("  1. Nome 'Karol' usado nas respostas (após msg 3)")
    print("  2. Nenhuma pergunta repetida")
    print("  3. Handoff acionado em msgs 10 e 12")
    print("  4. Facts salvos corretamente no Redis")
    print("="*60)

if __name__ == "__main__":
    main()
