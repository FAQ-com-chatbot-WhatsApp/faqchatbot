# Guia de Testes - API WhatsBot

## Pré-requisitos

Containers rodando:

```bash
docker ps
# Deve mostrar: webcore, whatsbot-db, whatsbot-phpmyadmin
```

## 1. Health Check

```bash
curl http://localhost:8080/api/health/ping
```

**Resposta esperada**:

```json
{
	"status": "ok",
	"time": "2025-11-06T16:30:00-03:00",
	"app": "WhatsBot"
}
```

---

## 2. Autenticação

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "lmswill",
    "password": "admin123"
  }'
```

**Resposta esperada**:

````json
{
	"access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
---

## 12. Messages - CRUD (Requer autenticação)

Listar mensagens (filtros: conversation_id, sender/direction, page/per_page):

```bash
curl -X GET "http://localhost:8080/api/messages?page=1&per_page=20&type=text&status=processed" \
	-H "Authorization: Bearer $TOKEN"
````

Resposta esperada (pagina):

```json
{
	"messages": [
		/* array de mensagens com campos id, conversation_id, sender, content, created_at */
	],
	"pagination": { "page": 1, "per_page": 20, "total": 0 }
}
```

Obter mensagem por id:

```bash
curl -X GET http://localhost:8080/api/messages/$MESSAGE_ID \
	-H "Authorization: Bearer $TOKEN"
```

Atualizar mensagem (PUT) — substitui conteúdo da mensagem:

```bash
curl -X PUT http://localhost:8080/api/messages/$MESSAGE_ID \
	-H "Content-Type: application/json" \
	-H "Authorization: Bearer $TOKEN" \
	-d '{"sender":"bot","content":"Resposta atualizada pelo teste"}'
```

Remover mensagem:

```bash
curl -X DELETE http://localhost:8080/api/messages/$MESSAGE_ID \
	-H "Authorization: Bearer $TOKEN"
```

---

## 13. Contacts - CRUD e busca por telefone (Requer autenticação)

Listar contatos:

```bash
curl -X GET "http://localhost:8080/api/contacts?page=1&per_page=20" \
	-H "Authorization: Bearer $TOKEN"
```

Criar contato:

```bash
curl -X POST http://localhost:8080/api/contacts \
	-H "Content-Type: application/json" \
	-H "Authorization: Bearer $TOKEN" \
	-d '{"name":"Contato Teste","phone":"+5511999000111"}'
```

Obter contato por id:

```bash
curl -X GET http://localhost:8080/api/contacts/$CONTACT_ID \
	-H "Authorization: Bearer $TOKEN"
```

Atualizar contato (PUT):

```bash
curl -X PUT http://localhost:8080/api/contacts/$CONTACT_ID \
	-H "Content-Type: application/json" \
	-H "Authorization: Bearer $TOKEN" \
	-d '{"name":"Contato Atualizado"}'
```

Buscar contato por telefone (URL encode se necessário):

```bash
curl -X GET "http://localhost:8080/api/contacts/phone/%2B5511999000111" \
	-H "Authorization: Bearer $TOKEN"
```

Remover contato (DELETE) - comportamento: soft-delete quando disponível

```bash
curl -X DELETE http://localhost:8080/api/contacts/$CONTACT_ID \
	-H "Authorization: Bearer $TOKEN"
```

Resposta esperada (com o esquema de soft-delete presente):

- Status 200 com JSON indicando o id e `deleted_at` atualizado, por exemplo:

```json
{
	"id": "xxxxxxxx-xxxx-....",
	"deleted_at": "2025-11-06T20:00:00Z"
}
```

Se a coluna `deleted_at` não existir no seu banco (migração não aplicada), o endpoint pode realizar um delete físico e retornar `204 No Content` ou `200 OK` dependendo da versão da API instalada. Verifique o comportamento no seu ambiente.

---

## 14. Conversations - Próximo passo (Requer autenticação)

Dispara a lógica que retorna o próximo passo do flow para uma conversa ativa:

```bash
curl -X POST http://localhost:8080/api/conversations/$CONV_ID/next \
	-H "Content-Type: application/json" \
	-H "Authorization: Bearer $TOKEN" \
	-d '{}'
```

Resposta esperada:

```json
{
	"conversation_id": "...",
	"next_step": { "id": "welcome", "type": "message", "content": "..." },
	"completed": false
}
```

Casos especiais / comportamento esperado

- 1. Conversa inativa por mais de 24 horas (timeout -> abandonada)

Descrição: se a última interação da conversa tiver ocorrido há mais de ~24h, a chamada a `/conversations/{id}/next` marcará a conversa como `abandoned` (status) e não retornará um próximo passo acionável.

Exemplo:

```bash
curl -X POST http://localhost:8080/api/conversations/$CONV_ID/next \
	-H "Content-Type: application/json" \
	-H "Authorization: Bearer $TOKEN" \
	-d '{}'
```

Resposta esperada (exemplo):

```json
{
	"conversation_id": "...",
	"status": "abandoned",
	"message": "conversation timed out due to inactivity",
	"completed": false
}
```

- 2. Fluxo já concluído (sem próximo passo)

Descrição: quando o fluxo chegou ao fim (não há mais steps), a chamada a `/conversations/{id}/next` sinaliza que a conversa foi finalizada e define `completed: true`.

Exemplo:

```bash
curl -X POST http://localhost:8080/api/conversations/$CONV_ID/next \
	-H "Content-Type: application/json" \
	-H "Authorization: Bearer $TOKEN" \
	-d '{}'
```

Resposta esperada (exemplo):

```json
{
	"conversation_id": "...",
	"status": "completed",
	"next_step": null,
	"completed": true
}
```

Observação: o comportamento exato (mensagens e campos) pode variar conforme versão da API; porém os efeitos esperados são: alteração de `status` para `abandoned` ou `completed` e `completed: true/false` no payload retornado.

---

    "expires_in": 900,
    "token_type": "Bearer"

}

````

**Salvar o token para próximas requisições**:

```bash
export TOKEN="seu_token_aqui"
````

---

## 3. Criar Conversa (Requer autenticação)

**Pré-requisito**: Buscar um flow_id válido:

```bash
docker exec -it whatsbot-db mysql -u botuser -ppwd123 botdb -e "SELECT id, name FROM flows WHERE status='active' LIMIT 1;"
```

Salvar o UUID retornado e usar abaixo:

```bash
curl -X POST http://localhost:8080/api/conversations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "contact_phone": "+5511999887766",
    "contact_name": "João Silva",
    "flow_id": "fb8dfb6f-bb46-11f0-be95-0a113649d94d"
  }'
```

**Resposta esperada**:

```json
{
	"conversation_id": "a1b2c3d4-e5f6-...",
	"contact_id": "uuid-do-contato",
	"flow_id": "fb8dfb6f-bb46-11f0-be95-0a113649d94d",
	"status": "active"
}
```

**Salvar conversation_id para próximos testes**:

```bash
export CONV_ID="conversation_id_aqui"
```

---

## 4. Adicionar Mensagem (Requer autenticação)

```bash
curl -X POST http://localhost:8080/api/messages \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
	-d '{
		"conversation_id": "'$CONV_ID'",
		"sender": "user",
		"content": "Olá, preciso de ajuda com meu pedido"
	}'
```

**Resposta esperada**:

```json
{
	"message_id": "msg-uuid-...",
	"conversation_id": "...",
	"sender": "user",
	"content": "Olá, preciso de ajuda com meu pedido"
}
```

Adicionar resposta do bot:

```bash
curl -X POST http://localhost:8080/api/messages \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
	-d '{
		"conversation_id": "'$CONV_ID'",
		"sender": "bot",
		"content": "Olá! Estou aqui para ajudar. Qual é o número do seu pedido?"
	}'
```

---

## 5. Buscar Conversa com Histórico (Requer autenticação)

```bash
curl -X GET http://localhost:8080/api/conversations/$CONV_ID \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta esperada (observação: content é JSON na base e é retornado como objeto)**:

```json
{
	"conversation": {
		"id": "...",
		"status": "active",
		"contact_id": 123,
		"phone": "+5511999887766",
		"contact_name": "João Silva",
		"flow_id": "...",
		"flow_name": "FAQ Suporte",
		"created_at": "2025-11-06 19:30:00",
		"updated_at": "2025-11-06 19:31:00"
	},
	"messages": [
		{
			"id": "...",
			"direction": "in",
			"type": "text",
			"content": { "text": "Olá, preciso de ajuda com meu pedido" },
			"created_at": "2025-11-06 19:30:15"
		},
		{
			"id": "...",
			"direction": "out",
			"type": "text",
			"content": {
				"text": "Olá! Estou aqui para ajudar. Qual é o número do seu pedido?"
			},
			"created_at": "2025-11-06 19:30:45"
		}
	]
}
```

---

## Testes de Validação e Erros

### 1. Tentativa de acesso sem token

```bash
curl -X POST http://localhost:8080/api/conversations \
  -H "Content-Type: application/json" \
  -d '{"contact_phone": "+5511999887766", "contact_name": "Teste", "flow_id": "123"}'
```

**Esperado**: Status 401 - `{"error": {"code": "UNAUTHORIZED", "message": "Token ausente"}}`

### 2. Token inválido

```bash
curl -X POST http://localhost:8080/api/conversations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer token_invalido" \
  -d '{"contact_phone": "+5511999887766", "contact_name": "Teste", "flow_id": "123"}'
```

**Esperado**: Status 401 - `{"error": {"code": "INVALID_TOKEN", "message": "Token inválido ou expirado"}}`

### 3. Telefone inválido

```bash
curl -X POST http://localhost:8080/api/conversations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"contact_phone": "telefone_invalido", "contact_name": "Teste", "flow_id": "123"}'
```

**Esperado**: Status 422 - `{"error": {"code": "INVALID_PHONE", "message": "Telefone inválido"}}`

### 4. Flow inexistente

```bash
curl -X POST http://localhost:8080/api/conversations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"contact_phone": "+5511999887766", "contact_name": "Teste", "flow_id": 99999}'
```

**Esperado**: Status 404 - `{"error": {"code": "FLOW_NOT_FOUND", "message": "Flow não encontrado"}}`

---

## Verificação no Banco de Dados

```bash
# Ver contatos criados
docker exec -it whatsbot-db mysql -u botuser -ppwd123 botdb -e "SELECT * FROM contacts;"

# Ver conversas
docker exec -it whatsbot-db mysql -u botuser -ppwd123 botdb -e "SELECT * FROM conversations;"

# Ver mensagens
docker exec -it whatsbot-db mysql -u botuser -ppwd123 botdb -e "SELECT * FROM messages ORDER BY created_at DESC LIMIT 10;"
```

---

## 6. Listar Conversas (Requer autenticação)

```bash
curl -X GET "http://localhost:8080/api/conversations?page=1&per_page=10&status=active" \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta esperada**:

```json
{
	"conversations": [
		{
			"id": "...",
			"status": "active",
			"contact_id": "...",
			"phone": "+5511999887766",
			"contact_name": "João Silva",
			"flow_id": "...",
			"flow_name": "FAQ Suporte",
			"created_at": "2025-11-06 19:30:00",
			"updated_at": "2025-11-06 19:31:00",
			"message_count": 2,
			"last_message_id": "..."
		}
	],
	"pagination": {
		"page": 1,
		"per_page": 10,
		"total": 1,
		"total_pages": 1
	}
}
```

---

## 7. Atualizar Status da Conversa (Requer autenticação)

```bash
curl -X PATCH http://localhost:8080/api/conversations/$CONV_ID \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "status": "completed"
  }'
```

**Resposta esperada**:

```json
{
	"status": "completed",
	"updated": true
}
```

---

## 8. Listar Flows (Requer autenticação)

```bash
curl -X GET "http://localhost:8080/api/flows?page=1&per_page=10&status=active" \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta esperada**:

```json
{
	"flows": [
		{
			"id": "...",
			"name": "FAQ Suporte",
			"version": 1,
			"status": "active",
			"description": "Flow para suporte ao cliente",
			"created_by": 1,
			"created_at": "2025-11-06 19:00:00",
			"updated_at": "2025-11-06 19:00:00"
		}
	],
	"pagination": {
		"page": 1,
		"per_page": 10,
		"total": 1,
		"total_pages": 1
	}
}
```

---

## 9. Criar Flow (Requer autenticação)

```bash
curl -X POST http://localhost:8080/api/flows \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "Fluxo de Suporte Técnico",
    "description": "Flow para atendimento de suporte técnico",
    "definition": {
      "steps": [
        {
          "id": "welcome",
          "type": "message",
          "content": "Olá! Como posso ajudar com suporte técnico?"
        }
      ]
    },
    "status": "draft"
  }'
```

**Resposta esperada**:

```json
{
	"flow_id": "uuid-do-flow-...",
	"name": "Fluxo de Suporte Técnico",
	"version": 1,
	"status": "draft",
	"description": "Flow para atendimento de suporte técnico",
	"definition": {
		"steps": [
			{
				"id": "welcome",
				"type": "message",
				"content": "Olá! Como posso ajudar com suporte técnico?"
			}
		]
	},
	"created_by": 1
}
```

---

## 10. Atualizar Flow (Requer autenticação)

```bash
# Primeiro, obter um flow_id existente
FLOW_ID=$(curl -X GET "http://localhost:8080/api/flows?page=1&per_page=1&status=draft" \
  -H "Authorization: Bearer $TOKEN" \
  -s | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

echo "Flow ID: $FLOW_ID"

# Atualizar o flow
curl -X PATCH http://localhost:8080/api/flows/$FLOW_ID \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "Fluxo Atualizado",
    "status": "active",
    "description": "Descrição atualizada"
  }'
```

**Resposta esperada**:

```json
{
	"updated": true,
	"flow_id": "uuid-do-flow"
}
```

---

## 11. Remover Flow (Requer autenticação)

```bash
# Criar um flow de teste para remoção
CREATE_RESPONSE=$(curl -X POST http://localhost:8080/api/flows \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -s \
  -d '{
    "name": "Flow para Teste de Remoção",
    "definition": {"steps": [{"id": "test", "type": "message", "content": "Teste"}]}
  }')

FLOW_ID=$(echo $CREATE_RESPONSE | grep -o '"flow_id":"[^"]*"' | cut -d'"' -f4)
echo "Flow criado para teste: $FLOW_ID"

# Remover o flow
curl -X DELETE http://localhost:8080/api/flows/$FLOW_ID \
  -H "Authorization: Bearer $TOKEN"
```

**Resposta esperada**:

```json
{
	"deleted": true,
	"flow_id": "uuid-do-flow"
}
```

**Nota**: Flows em uso por conversas ativas não podem ser removidos.

---

## Próximos Passos

1. ✅ Endpoints funcionais: health, auth, conversations, messages, list conversations, update status
2. [ ] Implementar testes automatizados
3. ✅ Adicionar endpoint `GET /flows` (listar flows)
4. ✅ Implementar endpoint `POST /flows` (criar flow)
5. ✅ Implementar endpoint `PATCH /flows/{id}` (atualizar flow)
6. ✅ Implementar endpoint `DELETE /flows/{id}` (remover flow)
7. [ ] Adicionar filtros avançados em listagens

---

**Última atualização**: 06/11/2025  
**Status**: Endpoints principais implementados
