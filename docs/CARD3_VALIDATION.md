# CARD 3 - Validação (CRUD de Flows)

Status: 100% implementado

## Endpoints implementados

- GET /flows (listagem com paginação, filtros `status` e `name`)
- GET /flows/{id} (detalhe com `definition` decodificada)
- POST /flows (criação com validação de steps e referências)
- PUT /flows/{id} (substituição com incremento de `version`)
- PATCH /flows/{id} (atualização parcial + incremento condicional de `version`)
- DELETE /flows/{id} (soft delete; bloqueia se há conversas ativas)
- POST /flows/{id}/activate (ativação)
- POST /flows/{id}/deactivate (desativação – exposto como `inactive`)
- POST /flows/{id}/duplicate (duplicação independente – `version=1`, `status=draft`)

Arquivo: `api/index.php`  
Linhas relevantes (aprox): 980–1415

## Validações

- Estrutura `definition`:
  - Exige `definition` como objeto
  - Exige `definition.steps` como array
  - Cada `message_id` referenciado em steps deve existir em `messages`
- Status aceitos na API: `draft`, `active`, `inactive` (mapeado para `archived` no DB)
- PUT/PATCH incrementam `version` quando `definition` muda

## Checklist de Validação

- [x] Criação de fluxo valida estrutura de steps
- [x] Steps fazem referência a mensagens existentes
- [x] Versionamento incrementa corretamente
- [x] Ativação/desativação altera status apropriadamente
- [x] Atualização mantém integridade dos dados
- [x] Exclusão realiza soft delete (quando coluna existe) e bloqueia uso em conversas ativas
- [x] Listagem filtra corretamente por status (aceita `inactive`)
- [x] Busca por nome retorna resultados relevantes (LIKE)
- [x] Duplicação cria cópia independente (`(copy)`, `version=1`, `status=draft`)

## Notas

- O banco usa `status = archived`; na API esse status é exposto como `inactive` para clareza.
- Definição de steps é flexível: a engine suporta array ordenado e, quando presente, usa `order` para ordenar.
- Conversas utilizam os flows via `flow_id`; desativação não impede leitura, mas `POST /conversations` exige flow `active`.
