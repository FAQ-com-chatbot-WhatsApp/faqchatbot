# Configuração do Banco de Dados

## Visão Geral

Este documento descreve como configurar e gerenciar o banco de dados do projeto WhatsBot.

## Estrutura do Banco

### Tabelas Principais

- **contacts** - Contatos do sistema
- **flows** - Fluxos conversacionais
- **conversations** - Conversas entre contatos e o sistema
- **messages** - Mensagens trocadas nas conversas
- **conversation_transitions** - Histórico de transições entre steps

### Diagrama de Relações

```
contacts (1) ─────< (N) conversations (N) >───── (1) flows
                           │
                           │
                           ├─────< (N) messages
                           │
                           └─────< (N) conversation_transitions
```

## Configuração Inicial

### 1. Variáveis de Ambiente

Configure o arquivo `.env` com as credenciais do banco:

```env
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=botdb
DB_USERNAME=bot_user
DB_PASSWORD=pwd123
DB_PREFIX=bak_lepgs_
```

### 2. Criar Database

```bash
docker exec whatsbot-db mysql -uroot -prootpassword123 -e "CREATE DATABASE IF NOT EXISTS botdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker exec whatsbot-db mysql -uroot -prootpassword123 -e "GRANT ALL PRIVILEGES ON botdb.* TO 'bot_user'@'%';"
```

### 3. Aplicar Migrations

#### Método 1: Script Automatizado (Recomendado)

```bash
# Aplicar todas as migrations em ordem
docker exec webcore php scripts/apply_migrations.php

# Resetar e reaplicar (CUIDADO: apaga todos os dados)
docker exec webcore php scripts/apply_migrations.php --reset
```

#### Método 2: Manual

```bash
# Schema base
docker exec -i whatsbot-db mysql -ubot_user -ppwd123 botdb < database/schema.sql

# Migrations incrementais
docker exec -i whatsbot-db mysql -ubot_user -ppwd123 botdb < migrations/001_add_soft_delete_and_current_step.sql
docker exec -i whatsbot-db mysql -ubot_user -ppwd123 botdb < migrations/001b_apply_changes.sql
docker exec -i whatsbot-db mysql -ubot_user -ppwd123 botdb < migrations/003_add_indices.sql

# Seed de dados de teste
docker exec -i whatsbot-db mysql -ubot_user -ppwd123 botdb < migrations/seed_sample_data.sql
```

## Verificação

### Listar Tabelas

```bash
docker exec whatsbot-db mysql -ubot_user -ppwd123 botdb -e "SHOW TABLES;"
```

### Verificar Estrutura

```bash
# Ver estrutura de uma tabela
docker exec whatsbot-db mysql -ubot_user -ppwd123 botdb -e "DESCRIBE contacts;"
docker exec whatsbot-db mysql -ubot_user -ppwd123 botdb -e "DESCRIBE flows;"
docker exec whatsbot-db mysql -ubot_user -ppwd123 botdb -e "DESCRIBE conversations;"
docker exec whatsbot-db mysql -ubot_user -ppwd123 botdb -e "DESCRIBE messages;"
```

### Contar Registros

```bash
docker exec whatsbot-db mysql -ubot_user -ppwd123 botdb -e "
SELECT
  'contacts' as tabela, COUNT(*) as registros FROM contacts
UNION ALL SELECT 'flows', COUNT(*) FROM flows
UNION ALL SELECT 'conversations', COUNT(*) FROM conversations
UNION ALL SELECT 'messages', COUNT(*) FROM messages;
"
```

## Soft Delete

As tabelas principais suportam soft delete através da coluna `deleted_at`:

- Quando um registro é "excluído", a coluna `deleted_at` é preenchida com a data/hora atual
- Registros com `deleted_at IS NOT NULL` são considerados excluídos
- Queries automáticas aplicam filtro `WHERE deleted_at IS NULL`

## Timestamps Automáticos

Todas as tabelas possuem:

- `created_at` - Data/hora de criação (auto-preenchido via `NOW()`)
- `updated_at` - Data/hora da última atualização (atualizado automaticamente)

## Models e Repositories

### Models Disponíveis

Localizados em `src/Models/`:

- `Contact` - Modelo de contato
- `Flow` - Modelo de fluxo
- `Conversation` - Modelo de conversa
- `Message` - Modelo de mensagem

### Repositories Disponíveis

Localizados em `src/Repositories/`:

- `ContactRepository` - CRUD de contatos
- `FlowRepository` - CRUD de flows
- `ConversationRepository` - CRUD de conversas
- `MessageRepository` - CRUD de mensagens

### Exemplo de Uso

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use WhatsBot\Repositories\ContactRepository;

// Obtém conexão PDO
$pdo = pdo(); // função helper do api/index.php

// Cria repository
$contactRepo = new ContactRepository($pdo);

// Buscar por ID
$contact = $contactRepo->findById('uuid-aqui');

// Criar novo contato
$newContact = $contactRepo->create([
    'name' => 'João Silva',
    'phone' => '+5511999887766'
]);

// Atualizar
$contactRepo->update($newContact->id, ['name' => 'João Santos']);

// Listar com paginação
$contacts = $contactRepo->list(page: 1, perPage: 10);

// Contar total
$total = $contactRepo->count();

// Soft delete
$contactRepo->delete($newContact->id);
```

## Smoke Tests

Execute os smoke tests para validar que o banco e a API estão funcionando:

```bash
docker exec webcore php scripts/smoke_tests.php
```

## Troubleshooting

### Erro: Access denied

Verifique as credenciais no `.env` e recrie o database com as permissões corretas.

### Migrations já aplicadas aparecem como "exists"

As migrations são idempotentes. Mensagens "exists" indicam que as estruturas já foram criadas anteriormente.

### Índice UNIQUE em contacts.phone

A migration `002_unique_contacts_phone.sql` está comentada e requer deduplicação manual antes de aplicar:

```bash
# Listar duplicatas
docker exec whatsbot-db mysql -ubot_user -ppwd123 botdb -e "
SELECT phone, COUNT(*) as count
FROM contacts
GROUP BY phone
HAVING count > 1;
"

# Após resolver duplicatas manualmente, aplique:
# docker exec -i whatsbot-db mysql -ubot_user -ppwd123 botdb < migrations/002_unique_contacts_phone.sql
```

## Backup e Restore

### Fazer Backup

```bash
docker exec whatsbot-db mysqldump -ubot_user -ppwd123 botdb > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar Backup

```bash
docker exec -i whatsbot-db mysql -ubot_user -ppwd123 botdb < backup_20250106_120000.sql
```

## Recursos Adicionais

- [Documentação MySQL 8.0](https://dev.mysql.com/doc/refman/8.0/en/)
- [MariaDB 10.8 Documentation](https://mariadb.com/kb/en/mariadb-1080-release-notes/)
