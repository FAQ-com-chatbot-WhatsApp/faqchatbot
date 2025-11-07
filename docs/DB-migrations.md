# DB Migrations recomendadas para a Fase 1/2

Este arquivo lista alterações/ migrações no esquema que recomendamos aplicar no banco de dados (dev/prod) para suportar soft-delete, histórico de transições e controle de progresso de conversas.

ATENÇÃO: execute em ambiente de desenvolvimento/backup antes de aplicar em produção.

## 1) Soft delete em `contacts`, `messages`, `flows`
Adicionar coluna `deleted_at` TIMESTAMP NULL e índices.

SQL (MySQL/MariaDB):

```sql
ALTER TABLE contacts ADD COLUMN deleted_at DATETIME NULL, ADD INDEX idx_contacts_deleted_at (deleted_at);
ALTER TABLE messages ADD COLUMN deleted_at DATETIME NULL, ADD INDEX idx_messages_deleted_at (deleted_at);
ALTER TABLE flows ADD COLUMN deleted_at DATETIME NULL, ADD INDEX idx_flows_deleted_at (deleted_at);
```

Após isso, atualize endpoints de DELETE para setar `deleted_at = NOW()` em vez de remover linhas fisicamente.

## 2) Campo current_step em `conversations`
Armazenar posição atual do fluxo (facilita retomada e auditoria).

```sql
ALTER TABLE conversations ADD COLUMN current_step INT DEFAULT 0, ADD INDEX idx_conversations_current_step (current_step);
```

A aplicação deve manter `current_step` sincronizado com `message_count` ou preferir `current_step` como fonte de verdade quando implementado.

## 3) Tabela de log de transições de conversa
Registrar cada transição (next, complete, timeout) para auditoria e debugging.

```sql
CREATE TABLE conversation_transitions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  conversation_id CHAR(36) NOT NULL,
  from_step INT NULL,
  to_step INT NULL,
  step_id VARCHAR(191) NULL,
  action VARCHAR(64) NOT NULL,
  payload JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ct_conversation (conversation_id),
  CONSTRAINT fk_ct_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 4) Índices adicionais e constraints
Recomenda-se revisar índices para queries frequentes (phone, flow_id, status) e adicionar `UNIQUE` em `contacts.phone` se desejado:

```sql
ALTER TABLE contacts ADD UNIQUE KEY uq_contacts_phone (phone);
```

Mas atenção: se houver telefones duplicados existentes, limpá-los antes.

## 5) Seeders e migrations (recomendado)
- Criar migrations usando sua ferramenta preferida (Phinx, Doctrine Migrations, Laravel Migrations, Flyway, etc.).
- Incluir seeds iniciais: admin user, exemplo de flow ativo, fluxo de teste, contatos de teste e conversas.

## 6) Notas de compatibilidade
- Antes de aplicar UNIQUE em `contacts.phone`, certifique-se de remover duplicatas e validar formatos.
- Ao introduzir soft-delete, ajustar todas as consultas para filtrar `deleted_at IS NULL`.
- Teste backups e restore antes de executar em produção.

---

Se quiser, aplico os patches SQL como arquivos `migrations/` (não executados) e adiciono instruções passo a passo para aplicá-los no ambiente Docker atual.