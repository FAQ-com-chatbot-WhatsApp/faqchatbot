# API Reference - Flows (Fluxos Conversacionais)

Base URL: `http://localhost:8080/api`

Autenticação: `Authorization: Bearer {token}` obrigatória em todos os endpoints.

Status suportados:

- Expostos pela API: `draft`, `active`, `inactive`
- No banco: `draft`, `active`, `archived` (mapeado para `inactive` na resposta)

## Estrutura da Definição (definition)

A definição de um fluxo é um objeto JSON com a propriedade `steps`:

```json
{
	"steps": [
		{
			"order": 1,
			"message_id": "<uuid>",
			"metadata": { "channel": "whatsapp" }
		},
		{ "order": 2, "message_id": "<uuid>" }
	]
}
```

Regras de validação:

- `definition` deve ser um objeto com `steps` (array)
- Cada item em `steps` deve ser um objeto
- `message_id` (se presente) deve existir na tabela `messages`
- Campo `order` é opcional (a engine ordena por `order` se existir)

---

## Endpoints

### POST /flows

Cria um novo fluxo.

Request:

```http
POST /api/flows
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Onboarding",
  "description": "Fluxo de boas-vindas",
  "status": "draft",
  "definition": {
    "steps": [
      { "order": 1, "message_id": "123e4567-e89b-12d3-a456-426614174000" }
    ]
  }
}
```

Response 201:

```json
{
	"flow_id": "550e8400-e29b-41d4-a716-446655440000",
	"name": "Onboarding",
	"version": 1,
	"status": "draft",
	"description": "Fluxo de boas-vindas",
	"definition": {
		"steps": [
			{ "order": 1, "message_id": "123e4567-e89b-12d3-a456-426614174000" }
		]
	},
	"created_by": 12
}
```

Erros:

- 422 INVALID_NAME, INVALID_DEFINITION, INVALID_STEPS, INVALID_STATUS, INVALID_REFERENCE

---

### GET /flows

Lista flows com filtros e paginação.

Query params:

- `page` (default 1), `per_page` (default 10, max 100)
- `status`: `draft`, `active`, `inactive`
- `name`: busca parcial por nome

Response 200:

```json
{
	"flows": [
		{
			"id": "...",
			"name": "...",
			"version": 2,
			"status": "inactive",
			"description": null,
			"created_by": 12,
			"created_at": "...",
			"updated_at": "..."
		}
	],
	"pagination": { "page": 1, "per_page": 10, "total": 5, "total_pages": 1 }
}
```

---

### GET /flows/{id}

Busca um flow com definição decodificada.

Response 200:

```json
{
	"id": "...",
	"name": "Onboarding",
	"version": 1,
	"status": "draft",
	"description": "...",
	"definition": { "steps": [] },
	"created_by": 12,
	"created_at": "...",
	"updated_at": "..."
}
```

---

### PUT /flows/{id}

Substitui um flow (incrementa versão).

Regras:

- Requer `name`, `status`, `definition` (com `steps`)
- Incrementa `version` automaticamente

---

### PATCH /flows/{id}

Atualiza parcialmente um flow.

- Incrementa `version` se `definition` mudou
- Aceita `status`: `draft`, `active`, `inactive`

---

### DELETE /flows/{id}

Remove um flow (soft delete quando disponível). Bloqueia se houver conversas ativas usando o flow.

---

### POST /flows/{id}/activate

Ativa um flow (status: `active`).

Response 200:

```json
{ "flow_id": "...", "status": "active" }
```

---

### POST /flows/{id}/deactivate

Desativa um flow (status exposto: `inactive`).

Response 200:

```json
{ "flow_id": "...", "status": "inactive" }
```

---

### POST /flows/{id}/duplicate

Cria uma cópia independente do flow (nome com sufixo ` (copy)`), `version=1`, `status=draft`.

Response 200:

```json
{
	"flow_id": "...",
	"name": "Onboarding (copy)",
	"version": 1,
	"status": "draft"
}
```

---

## Observações

- Status `inactive` é mapeado internamente para `archived` no banco.
- A engine de conversação (POST /conversations/{id}/next) usa a ordem dos steps; quando presente, respeita `order`.
