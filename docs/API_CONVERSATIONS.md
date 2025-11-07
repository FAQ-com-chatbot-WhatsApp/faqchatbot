# API Reference - Conversas (Conversation Engine)

Base URL: `http://localhost:8080/api`

Autenticação: obrigatória em todos os endpoints (JWT no header Authorization).

## Estrutura de conversa

- status: `active` | `completed` | `abandoned`
- current_step: índice do próximo step a ser servido (inicia em 0)
- message_count: contador total de mensagens trocadas na conversa

## Endpoints

### POST /conversations

Inicia nova conversa a partir de um fluxo ativo.

Body:

```json
{
	"contact_phone": "+5511999887766",
	"contact_name": "João Silva",
	"flow_id": "<uuid>"
}
```

Regras:

- `flow_id` deve existir e estar `active`
- Cria contato se não existir (atualiza nome se mudou)

Response 201:

```json
{
	"conversation_id": "<uuid>",
	"contact_id": "<uuid>",
	"flow_id": "<uuid>",
	"status": "active"
}
```

---

### GET /conversations/{id}

Retorna dados da conversa + mensagens associadas.

Response 200 (exemplo):

```json
{
	"conversation": {
		"id": "<uuid>",
		"status": "active",
		"message_count": 2,
		"last_message_id": null,
		"contact_id": "<uuid>",
		"contact_name": "João",
		"phone": "+55...",
		"flow_id": "<uuid>",
		"flow_name": "Onboarding"
	},
	"messages": [
		{
			"id": "<uuid>",
			"direction": "in",
			"type": "text",
			"content": { "body": "..." },
			"status": "processed"
		}
	]
}
```

---

### GET /conversations

Lista conversas com filtros e paginação.

Query params:

- `page`, `per_page`
- `status`: `active`, `completed`, `abandoned`
- `contact_phone`: filtro por telefone do contato (LIKE)

---

### POST /conversations/{id}/next

Avança a conversa para o próximo step do fluxo.

Regras:

- Usa `current_step` para determinar o próximo step
- Incrementa `current_step` e `message_count` ao servir o step
- Timeout: se `updated_at` > 24h, conversa é marcada `abandoned` e não avança
- Finalização: quando não há próximo step, conversa vira `completed`
- Log: insere registros em `conversation_transitions` com `action=next` e `action=complete`

Response 200:

```json
{
	"conversation_id": "<uuid>",
	"next_step": {
		"order": 1,
		"message_id": "<uuid>",
		"message": { "id": "<uuid>", "type": "text", "content": { "body": "..." } }
	},
	"completed": false,
	"status": "active"
}
```

---

### PATCH /conversations/{id}

Atualiza status manualmente.

Body:

```json
{ "status": "abandoned" }
```

---

### GET /conversations/active/phone/{phone}

Identifica a conversa ativa mais recente por telefone do contato.

Response 200:

```json
{
	"conversation": {
		"id": "<uuid>",
		"status": "active",
		"current_step": 2,
		"message_count": 5,
		"contact_id": "<uuid>",
		"phone": "+55...",
		"flow_id": "<uuid>",
		"flow_name": "Onboarding"
	}
}
```

Erros comuns: 404 NOT_FOUND quando não há conversa ativa.
