# No Boss FAQ - API Web Services

Este plugin registra endpoints REST para consulta e manipulação de dados do `com_nobossfaq`.

## Pré-requisitos

- Plugin `Web Services - No Boss FAQ` habilitado.
- Usuário autenticado com permissão `core.manage` em `com_nobossfaq`.
- URL base da API (recomendado para maior compatibilidade): `https://SEU_DOMINIO/api/index.php`.

## Autenticação

Exemplo com Bearer Token:

```bash
-H "Authorization: Bearer SEU_TOKEN"
```

## Testes rápidos (Postman e CLI)

### A) Postman (`postman_collection.json`)

Este plugin inclui o arquivo `postman_collection.json` para importar no Postman.

Passo a passo (interface em inglês):

1. Abra o Postman e clique em **Import**.
2. Selecione o arquivo `postman_collection.json`.
3. Na coluna da esquerda, clique na collection **No Boss FAQ API**.
4. Abra a aba **Variables** da collection.
5. Preencha os campos:
  - `baseUrl` = `https://SEU_DOMINIO/api/index.php`
  - `token` = seu token Bearer gerado no Joomla
  - `group_id`, `category_id`, `question_id` conforme seus dados
6. Clique em **Save**.
7. Execute primeiro **Groups - Listar** para validar conexão e autenticação.

Observação:
- Em versões recentes do Postman, ajuste os valores em **Current Value** (e opcionalmente em **Initial Value**).
- Se abrir apenas `https://SEU_DOMINIO/api/index.php` no navegador, é esperado retornar `404 Resource not found` porque não há endpoint na raiz; use sempre `/v1/nobossfaq/...`.

### B) Script PHP CLI (`test_nobossfaq_api.php`)

Este plugin inclui o script `test_nobossfaq_api.php` para validar os endpoints via terminal.

Como usar:

1. Abra o arquivo e ajuste as variáveis com `FIXME`:
  - `$baseUrl`
  - `$token`
2. Execute no terminal:

```bash
php plugins/webservices/nobossfaq/test_nobossfaq_api.php
```

O script faz chamadas de exemplo para todos os endpoints e imprime as respostas.

Importante:
- O script é bloqueado para acesso via URL e executa somente via CLI.
- Em ambiente local com certificado autoassinado, o script já está configurado para testes.

## Configurar usuário de API (Joomla)

### 1) Habilitar plugins necessários

- `API Authentication - Web Services Joomla Token` (grupo `api-authentication`)
- `Web Services - No Boss FAQ` (este plugin)

### 2) Criar/usar um usuário técnico de API

- Acesse `Usuários > Gerenciar` e crie um usuário exclusivo para integrações.
- Recomenda-se não usar usuário pessoal para consumo da API.

### 3) Definir permissões mínimas no componente

Em `Sistema > Gerenciar > Extensões > No Boss FAQ > Permissões`:

- Para leitura (`GET`):
  - `Acesso à Administração (core.manage)` = **Permitido**
- Para escrita (`POST/PATCH/DELETE`):
  - `Criar (core.create)` = **Permitido**
  - `Editar (core.edit)` = **Permitido**
  - `Excluir (core.delete)` = **Permitido**
  - `Editar Estado (core.edit.state)` = **Permitido**

Observação: se preferir setup rápido para testes, use grupo `Super Users`.

### 4) Gerar token do usuário

- No perfil do usuário técnico, abra a aba de token de API.
- Gere/copie o token e use no header:

```bash
Authorization: Bearer SEU_TOKEN
```

### 5) Teste básico de conectividade

```bash
curl -X GET "https://SEU_DOMINIO/api/v1/nobossfaq/groups" \
  -H "Accept: application/json, application/vnd.api+json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

## Endpoints

### 1) Listar grupos

`GET /v1/nobossfaq/groups`

Parâmetros obrigatórios:
- Nenhum

Parâmetros opcionais:
- `state` (int): padrão `1`
- `language` (string): ex.: `pt-BR`

Exemplo:

```bash
curl -X GET "https://SEU_DOMINIO/api/v1/nobossfaq/groups?state=1&language=pt-BR" \
  -H "Accept: application/json, application/vnd.api+json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

---

### 2) Listar categorias de um grupo

`GET /v1/nobossfaq/categories`

Parâmetros obrigatórios:
- Nenhum

Parâmetros opcionais:
- `group_id` (opcional)
- `language` (opcional)

Exemplo:

```bash
curl -X GET "https://SEU_DOMINIO/api/v1/nobossfaq/categories?group_id=3&language=pt-BR" \
  -H "Accept: application/json, application/vnd.api+json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

---

### 3) Listar perguntas e respostas (com filtros)

`GET /v1/nobossfaq/questions`

Parâmetros obrigatórios:
- Nenhum

Parâmetros opcionais:
- `group_id` (opcional)
- `category_id` (opcional)
- `keywords` (opcional)
- `answer_type` (opcional: `editor`, `article`, `local-file`, `external-url`)
- `language` (opcional)

Exemplo:

```bash
curl -X GET "https://SEU_DOMINIO/api/v1/nobossfaq/questions?category_id=10&keywords=pagamento+boleto&answer_type=editor&language=pt-BR" \
  -H "Accept: application/json, application/vnd.api+json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

---

### 4) Obter uma pergunta específica

`GET /v1/nobossfaq/questions/:id`

Parâmetros obrigatórios:
- `id` (na rota)

Parâmetros opcionais:
- Nenhum

Exemplo:

```bash
curl -X GET "https://SEU_DOMINIO/api/v1/nobossfaq/questions/25" \
  -H "Accept: application/json, application/vnd.api+json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

---

### 5) Criar pergunta

`POST /v1/nobossfaq/questions`

Parâmetros obrigatórios (body JSON):
- `question`
- `id_faqs_group`
- `id_category`
- `answer`

Parâmetros opcionais (body JSON):
- `state`
- `language`

Campos de resposta considerados no `POST`:
- `answer_type` fixo como `editor` (definido pelo backend)
- `answer`

Observação: no `POST /v1/nobossfaq/questions` não são tratados os campos `answer_file`, `answer_url_video` e `answer_article`.

Exemplo (`editor`):

```bash
curl -X POST "https://SEU_DOMINIO/api/v1/nobossfaq/questions" \
  -H "Accept: application/json, application/vnd.api+json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{
    "question": "Quais são as formas de pagamento?",
    "id_faqs_group": 3,
    "id_category": 10,
    "answer": "Aceitamos cartão, PIX e boleto.",
    "state": 1,
    "language": "pt-BR"
  }'
```

---

### 6) Editar pergunta

`PATCH /v1/nobossfaq/questions/:id`

Parâmetros obrigatórios:
- `id` (na rota)

Parâmetros opcionais (body JSON):
- `question`
- `id_category`
- `state`
- `language`
- `answer_type`
- `answer`
- `answer_file`
- `answer_url_video`
- `answer_article`

Exemplo:

```bash
curl -X PATCH "https://SEU_DOMINIO/api/v1/nobossfaq/questions/25" \
  -H "Accept: application/json, application/vnd.api+json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{
    "question": "Quais formas de pagamento vocês aceitam?",
    "answer_type": "external-url",
    "answer_url_video": "https://www.youtube.com/watch?v=upwjtMl6fm4"
  }'
```

---

### 7) Excluir pergunta

`DELETE /v1/nobossfaq/questions/:id`

Parâmetros obrigatórios:
- `id` (na rota)

Parâmetros opcionais:
- Nenhum

Exemplo:

```bash
curl -X DELETE "https://SEU_DOMINIO/api/v1/nobossfaq/questions/25" \
  -H "Accept: application/json, application/vnd.api+json" \
  -H "Authorization: Bearer SEU_TOKEN"
```

## Observações

- O endpoint de perguntas respeita as configurações do grupo para exibição de conteúdo do componente e/ou artigos.
- O filtro `keywords` usa busca por palavras em campos de pergunta, resposta e categoria.
- Perguntas com `answer_type=article` retornam resposta com conteúdo do artigo vinculado.
- As URLs acima consideram API sem `index.php` (URL amigável/rewrite habilitado no Joomla).
