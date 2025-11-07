-- Migration 003: additional indices for performance
-- Safe, compatibility-focused migration for MySQL/MariaDB
-- Run in development (example):
--   docker exec -i whatsbot-db sh -c 'mysql -u botuser -ppwd123 botdb' < migrations/003_add_indices.sql

-- IMPORTANT:
-- 1) Faça backup do banco antes de rodar em produção.
--    mysqldump -u USER -pPASSWORD DB_NAME > backup_before_003.sql
-- 2) Verifique impacto em tabelas grandes; experimente em staging primeiro.

-- This script checks for existing indexes and creates them only when absent.
-- This approach avoids "CREATE INDEX IF NOT EXISTS" which is not supported on older MySQL.

SET @schema := DATABASE();

-- Helper: create index if missing (table, index_name, columns)
-- Usage: call the block below for each index

-- messages.idx_messages_type
SET @tbl := 'messages';
SET @idx := 'idx_messages_type';
SELECT COUNT(1) INTO @exists FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND INDEX_NAME = @idx;
SET @sql = IF(@exists = 0,
	CONCAT('ALTER TABLE ', @tbl, ' ADD INDEX ', @idx, ' (type);'),
	'SELECT "index exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- messages.idx_messages_created_at
SET @tbl := 'messages';
SET @idx := 'idx_messages_created_at';
SELECT COUNT(1) INTO @exists FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND INDEX_NAME = @idx;
SET @sql = IF(@exists = 0,
	CONCAT('ALTER TABLE ', @tbl, ' ADD INDEX ', @idx, ' (created_at);'),
	'SELECT "index exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- contacts.idx_contacts_updated_at
SET @tbl := 'contacts';
SET @idx := 'idx_contacts_updated_at';
SELECT COUNT(1) INTO @exists FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND INDEX_NAME = @idx;
SET @sql = IF(@exists = 0,
	CONCAT('ALTER TABLE ', @tbl, ' ADD INDEX ', @idx, ' (updated_at);'),
	'SELECT "index exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- flows.idx_flows_updated_at
SET @tbl := 'flows';
SET @idx := 'idx_flows_updated_at';
SELECT COUNT(1) INTO @exists FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND INDEX_NAME = @idx;
SET @sql = IF(@exists = 0,
	CONCAT('ALTER TABLE ', @tbl, ' ADD INDEX ', @idx, ' (updated_at);'),
	'SELECT "index exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- conversation_transitions.idx_ct_conversation (create only if table exists)
SET @tbl := 'conversation_transitions';
SELECT COUNT(1) INTO @tbl_exists FROM information_schema.TABLES
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl;
SET @idx := 'idx_ct_conversation';
SET @sql = IF(@tbl_exists = 1,
	(SELECT IF(COUNT(1)=0, CONCAT('ALTER TABLE ', @tbl, ' ADD INDEX ', @idx, ' (conversation_id);'), 'SELECT "index exists"') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = @tbl AND INDEX_NAME = @idx),
	'SELECT "table missing"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- End of migration 003
