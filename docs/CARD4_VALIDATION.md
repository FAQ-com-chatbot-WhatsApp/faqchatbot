# CARD 4 - Validação (Motor de Conversação)

Status: Implementado e validado estruturalmente

## Endpoints envolvidos

- POST /conversations (criar)
- GET /conversations/{id} (status + mensagens)
- GET /conversations (listar com filtros)
- POST /conversations/{id}/next (próximo passo)
- PATCH /conversations/{id} (atualizar status)
- GET /conversations/active/phone/{phone} (identificar conversa ativa por telefone)

## Itens do checklist

- [x] Nova conversa é criada com fluxo válido (exige flow `active`)
- [x] Próximo passo avança corretamente no fluxo (usa `current_step`, incrementa e loga)
- [x] Conversa ativa é identificada pelo telefone (endpoint dedicado)
- [x] Estado da conversa é persistido corretamente (`current_step`, `message_count`)
- [x] Timeout encerra conversas inativas (>24h marca `abandoned` em /next)
- [x] Fluxo completo finaliza conversa automaticamente (`completed` em /next)
- [x] Conversa não inicia com fluxo inativo (validação no POST /conversations)
- [x] Logs registram transições (tabela `conversation_transitions`: actions `next` e `complete`)
- [x] Status da conversa reflete estado atual (respostas incluem `status`)

## Observações

- `current_step` inicia em 0 e é incrementado a cada `/next` bem sucedido.
- `message_count` também é incrementado para telemetria; mensagens reais são independentes do motor.
- `next` expande `message_id` quando presente no step, embutindo a mensagem no payload.
