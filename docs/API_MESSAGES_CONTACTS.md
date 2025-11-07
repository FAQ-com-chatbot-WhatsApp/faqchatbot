# API Reference - Mensagens e Contatos

## Visão Geral

Esta documentação cobre os endpoints REST para gerenciamento de mensagens e contatos no sistema WhatsBot.

**Base URL**: `http://localhost:8080/api`

**Autenticação**: Todos os endpoints requerem JWT token via header `Authorization: Bearer {token}`

---

## Mensagens

### POST /messages

Cria uma nova mensagem em uma conversa.

**Request**

```http
POST /api/messages
Authorization: Bearer {token}
Content-Type: application/json

{
  "conversation_id": "550e8400-e29b-41d4-a716-446655440000",
  "sender": "user",
  "content": "Olá, preciso de ajuda!",
  "type": "text",
  "metadata": {
    "source": "whatsapp",
    "timestamp": "2025-11-07T10:30:00Z"
  }
}
```

**Parâmetros**

| Campo             | Tipo          | Obrigatório | Descrição                              |
| ----------------- | ------------- | ----------- | -------------------------------------- |
| `conversation_id` | UUID          | Sim         | ID da conversa                         |
| `sender`          | string        | Sim         | `user` ou `bot`                        |
| `content`         | string/object | Sim         | Texto da mensagem ou objeto com `body` |
| `type`            | string        | Não         | Tipo da mensagem (padrão: `text`)      |
| `metadata`        | object        | Não         | Metadados adicionais                   |

**Response (201 Created)**

```json
{
	"message_id": "123e4567-e89b-12d3-a456-426614174000",
	"conversation_id": "550e8400-e29b-41d4-a716-446655440000",
	"direction": "in",
	"type": "text",
	"content": {
		"text": "Olá, preciso de ajuda!"
	},
	"status": "processed"
}
```

**Erros**

- `422 INVALID_CONVERSATION` - ID de conversa inválido
- `422 INVALID_SENDER` - Sender deve ser 'user' ou 'bot'
- `422 INVALID_CONTENT` - Conteúdo inválido ou vazio
- `404 CONVERSATION_NOT_FOUND` - Conversa não encontrada

---

### GET /messages

Lista mensagens com filtros e paginação.

**Request**

```http
GET /api/messages?page=1&per_page=20&conversation_id={uuid}&type=text&status=processed
Authorization: Bearer {token}
```

**Query Parameters**

| Parâmetro         | Tipo   | Descrição                                                              |
| ----------------- | ------ | ---------------------------------------------------------------------- |
| `page`            | int    | Número da página (padrão: 1)                                           |
| `per_page`        | int    | Itens por página (1-200, padrão: 20)                                   |
| `conversation_id` | UUID   | Filtrar por conversa                                                   |
| `direction`       | string | `in` (user) ou `out` (bot)                                             |
| `type`            | string | Tipo de mensagem (`text`, `image`, etc)                                |
| `status`          | string | Status (`pending`, `sent`, `delivered`, `read`, `failed`, `processed`) |

**Response (200 OK)**

```json
{
	"messages": [
		{
			"id": "123e4567-e89b-12d3-a456-426614174000",
			"conversation_id": "550e8400-e29b-41d4-a716-446655440000",
			"direction": "in",
			"type": "text",
			"content": {
				"body": "Olá, preciso de ajuda!",
				"metadata": {
					"source": "whatsapp"
				}
			},
			"status": "processed",
			"created_at": "2025-11-07 10:30:15"
		}
	],
	"pagination": {
		"page": 1,
		"per_page": 20,
		"total": 150,
		"total_pages": 8
	}
}
```

---

### GET /messages/{id}

Busca uma mensagem específica por ID.

**Request**

```http
GET /api/messages/123e4567-e89b-12d3-a456-426614174000
Authorization: Bearer {token}
```

**Response (200 OK)**

```json
{
	"id": "123e4567-e89b-12d3-a456-426614174000",
	"conversation_id": "550e8400-e29b-41d4-a716-446655440000",
	"direction": "in",
	"type": "text",
	"content": {
		"body": "Olá, preciso de ajuda!"
	},
	"status": "processed",
	"created_at": "2025-11-07 10:30:15"
}
```

**Erros**

- `404 NOT_FOUND` - Mensagem não encontrada

---

### PUT /messages/{id}

Atualiza uma mensagem existente.

**Request**

```http
PUT /api/messages/123e4567-e89b-12d3-a456-426614174000
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Mensagem corrigida",
  "type": "text",
  "status": "delivered"
}
```

**Response (200 OK)**

```json
{
	"updated": true,
	"message_id": "123e4567-e89b-12d3-a456-426614174000"
}
```

**Erros**

- `404 NOT_FOUND` - Mensagem não encontrada
- `422 INVALID_CONTENT` - Conteúdo obrigatório

---

### DELETE /messages/{id}

Remove uma mensagem (soft delete).

**Request**

```http
DELETE /api/messages/123e4567-e89b-12d3-a456-426614174000
Authorization: Bearer {token}
```

**Response (200 OK)**

```json
{
	"deleted": true,
	"message_id": "123e4567-e89b-12d3-a456-426614174000"
}
```

**Erros**

- `404 NOT_FOUND` - Mensagem não encontrada

---

## Contatos

### POST /contacts

Cria um novo contato ou retorna existente se telefone já cadastrado.

**Request**

```http
POST /api/contacts
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "João Silva",
  "phone": "+5511999887766"
}
```

**Parâmetros**

| Campo   | Tipo   | Obrigatório | Validação                                                       |
| ------- | ------ | ----------- | --------------------------------------------------------------- |
| `name`  | string | Sim         | 1-191 caracteres                                                |
| `phone` | string | Sim         | Formato: `+5511999887766` (6-32 chars, aceita espaços e hífens) |

**Response (201 Created - Novo contato)**

```json
{
	"contact_id": "550e8400-e29b-41d4-a716-446655440000",
	"name": "João Silva",
	"phone": "+5511999887766"
}
```

**Response (200 OK - Contato existente)**

```json
{
	"contact_id": "550e8400-e29b-41d4-a716-446655440000",
	"name": "João Silva",
	"phone": "+5511999887766"
}
```

> **Nota**: Se o telefone já existe, retorna o contato existente e atualiza o nome se for diferente (comportamento idempotente).

**Erros**

- `422 INVALID_NAME` - Nome inválido (vazio ou muito longo)
- `422 INVALID_PHONE` - Formato de telefone inválido

---

### GET /contacts

Lista contatos com filtros e paginação.

**Request**

```http
GET /api/contacts?page=1&per_page=20&phone=5511&name=João
Authorization: Bearer {token}
```

**Query Parameters**

| Parâmetro  | Tipo   | Descrição                            |
| ---------- | ------ | ------------------------------------ |
| `page`     | int    | Número da página (padrão: 1)         |
| `per_page` | int    | Itens por página (1-200, padrão: 20) |
| `phone`    | string | Busca parcial por telefone (LIKE)    |
| `name`     | string | Busca parcial por nome (LIKE)        |

**Response (200 OK)**

```json
{
	"contacts": [
		{
			"id": "550e8400-e29b-41d4-a716-446655440000",
			"name": "João Silva",
			"phone": "+5511999887766",
			"created_at": "2025-11-01 14:20:00",
			"updated_at": "2025-11-07 10:30:00"
		}
	],
	"pagination": {
		"page": 1,
		"per_page": 20,
		"total": 450,
		"total_pages": 23
	}
}
```

---

### GET /contacts/{id}

Busca um contato específico por ID.

**Request**

```http
GET /api/contacts/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer {token}
```

**Response (200 OK)**

```json
{
	"id": "550e8400-e29b-41d4-a716-446655440000",
	"name": "João Silva",
	"phone": "+5511999887766",
	"created_at": "2025-11-01 14:20:00",
	"updated_at": "2025-11-07 10:30:00"
}
```

**Erros**

- `404 NOT_FOUND` - Contato não encontrado

---

### GET /contacts/phone/{phone}

Busca um contato por número de telefone.

**Request**

```http
GET /api/contacts/phone/%2B5511999887766
Authorization: Bearer {token}
```

> **Nota**: O telefone deve ser URL-encoded. Exemplo: `+5511999887766` → `%2B5511999887766`

**Response (200 OK)**

```json
{
	"id": "550e8400-e29b-41d4-a716-446655440000",
	"name": "João Silva",
	"phone": "+5511999887766",
	"created_at": "2025-11-01 14:20:00",
	"updated_at": "2025-11-07 10:30:00"
}
```

**Erros**

- `404 NOT_FOUND` - Contato não encontrado

---

### PUT /contacts/{id}

Atualiza um contato existente.

**Request**

```http
PUT /api/contacts/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "João Santos",
  "phone": "+5511999887766"
}
```

**Response (200 OK)**

```json
{
	"updated": true,
	"contact_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

> **Nota**: O campo `updated_at` é atualizado automaticamente, representando a última interação.

**Erros**

- `404 NOT_FOUND` - Contato não encontrado
- `422 INVALID_NAME` - Nome inválido
- `422 INVALID_PHONE` - Formato de telefone inválido

---

### DELETE /contacts/{id}

Remove um contato (soft delete).

**Request**

```http
DELETE /api/contacts/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer {token}
```

**Response (200 OK)**

```json
{
	"deleted": true,
	"contact_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Erros**

- `404 NOT_FOUND` - Contato não encontrado

---

## Códigos de Erro Padrão

| Código HTTP | Código Interno         | Descrição                       |
| ----------- | ---------------------- | ------------------------------- |
| 401         | -                      | Token ausente ou inválido       |
| 404         | NOT_FOUND              | Recurso não encontrado          |
| 422         | INVALID_NAME           | Nome inválido                   |
| 422         | INVALID_PHONE          | Telefone inválido               |
| 422         | INVALID_CONVERSATION   | ID de conversa inválido         |
| 422         | INVALID_SENDER         | Sender deve ser 'user' ou 'bot' |
| 422         | INVALID_CONTENT        | Conteúdo inválido ou vazio      |
| 404         | CONVERSATION_NOT_FOUND | Conversa não encontrada         |
| 500         | INTERNAL               | Erro interno do servidor        |

---

## Exemplos de Uso com cURL

### Criar mensagem

```bash
curl -X POST http://localhost:8080/api/messages \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": "550e8400-e29b-41d4-a716-446655440000",
    "sender": "user",
    "content": "Olá!"
  }'
```

### Listar mensagens de uma conversa

```bash
curl -X GET "http://localhost:8080/api/messages?conversation_id=550e8400-e29b-41d4-a716-446655440000&page=1" \
  -H "Authorization: Bearer {token}"
```

### Criar contato

```bash
curl -X POST http://localhost:8080/api/contacts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "phone": "+5511999887766"
  }'
```

### Buscar contato por telefone

```bash
curl -X GET "http://localhost:8080/api/contacts/phone/%2B5511999887766" \
  -H "Authorization: Bearer {token}"
```

---

## Notas Importantes

1. **Soft Delete**: Recursos deletados não são removidos fisicamente, apenas marcados com `deleted_at`. Eles não aparecem em listagens.

2. **Last Interaction**: O campo `updated_at` de contatos é atualizado automaticamente quando:

   - Contato é atualizado via PUT
   - Nova mensagem é criada na conversa do contato

3. **Prevenção de Duplicatas**: POST /contacts verifica se telefone já existe e retorna o existente (comportamento idempotente).

4. **Validação de Telefone**: Aceita formatos internacionais com `+`, espaços e hífens. Exemplos válidos:

   - `+5511999887766`
   - `+55 11 99988-7766`
   - `5511999887766`

5. **Content de Mensagens**: Pode ser string simples ou objeto JSON. Sempre retornado como objeto decodificado nas listagens.

---

## Rate Limiting

Não implementado atualmente. Todos os endpoints estão sujeitos apenas à autenticação JWT.

---

## Versionamento

API versão: **1.0**  
Base URL atual: `/api` (sem versionamento no path)

Para futuras versões, considerar `/api/v2`, `/api/v3`, etc.
