#!/bin/bash

# Simulação conversa Karol - 13 mensagens
# Objetivo: Validar memória, anti-repetição, handoff

BASE_URL="http://localhost:3333/api/v1/webhooks/waha"
PHONE="555198098876@c.us"

send_msg() {
    local body="$1"
    local note="$2"
    
    echo ""
    echo "=========================================="
    echo "📤 $note"
    echo "   Mensagem: \"$body\""
    echo "=========================================="
    
    curl -s -X POST "$BASE_URL" \
      -H "Content-Type: application/json" \
      -d "{\"event\":\"message\",\"session\":\"default\",\"payload\":{\"from\":\"$PHONE\",\"body\":\"$body\",\"timestamp\":$(date +%s)}}"
    
    echo ""
    echo "✅ Enviada"
    sleep 1
}

echo "🧪 SIMULAÇÃO COMPLETA - CONVERSA KAROL"
echo "Validando: memória, anti-repetição, handoff"
echo ""

send_msg "oi" "Msg 1: Saudação"
send_msg "vi a clinica pelo instagram" "Msg 2: Discovery channel"
send_msg "me chamo karol" "Msg 3: ⚠️ NOME - deve extrair e usar"
send_msg "quero saber sobre terapia hormonal" "Msg 4: Interesse TRH"
send_msg "ja fiz antes mas não deu certo" "Msg 5: ⚠️ Experiência - NÃO perguntar 'já fez?'"
send_msg "me senti mal com os efeitos" "Msg 6: Problema específico"
send_msg "queria algo mais natural" "Msg 7: Necessidade"
send_msg "quanto custa a consulta?" "Msg 8: Pergunta valor"
send_msg "humm ta ok" "Msg 9: Aceitação"
send_msg "tem disponibilidade essa semana?" "Msg 10: ⚠️ HANDOFF calendário"
send_msg "prefiro de tarde" "Msg 11: Preferência horário"
send_msg "pode parcelar?" "Msg 12: ⚠️ HANDOFF pagamento"
send_msg "ok vou aguardar" "Msg 13: Finalização"

echo ""
echo "=========================================="
echo "✅ 13 MENSAGENS ENVIADAS"
echo "=========================================="
echo ""
echo "Aguarde ~90 segundos (debounce + anti-ban) e analise logs:"
echo "  docker logs tic-worker-1 --tail=500 | grep -E 'MEMORY|HANDOFF|Karol|patient_name'"
echo ""
echo "CHECKPOINTS ESPERADOS:"
echo "  1. Nome 'Karol' salvo e usado nas respostas (msg 3+)"
echo "  2. ZERO perguntas repetidas"
echo "  3. Fact 'has_done_procedure_before=true' salvo (msg 5)"
echo "  4. Handoff trigger 'calendar_access' (msg 10)"
echo "  5. Handoff trigger 'payment_question' (msg 12)"
