-- Migration 001: add soft-delete columns and current_step and conversation_transitions table
-- Run in development: docker exec -i whatsbot-db mysql -u botuser -ppwd123 botdb < migrations/001_add_soft_delete_and_current_step.sql

-- Migration 001b: idempotent add of deleted_at/current_step and conversation_transitions
-- Uses information_schema + PREPARE to be safe across MySQL/MariaDB versions

SET @schema := DATABASE();

-- contacts.deleted_at
SELECT COUNT(1) INTO @exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'contacts' AND COLUMN_NAME = 'deleted_at';
SET @sql = IF(@exists = 0, 'ALTER TABLE contacts ADD COLUMN deleted_at DATETIME NULL', 'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT COUNT(1) INTO @exists FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'contacts' AND INDEX_NAME = 'idx_contacts_deleted_at';
SET @sql = IF(@exists = 0, 'ALTER TABLE contacts ADD INDEX idx_contacts_deleted_at (deleted_at)', 'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- messages.deleted_at
SELECT COUNT(1) INTO @exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'deleted_at';
SET @sql = IF(@exists = 0, 'ALTER TABLE messages ADD COLUMN deleted_at DATETIME NULL', 'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT COUNT(1) INTO @exists FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'messages' AND INDEX_NAME = 'idx_messages_deleted_at';
SET @sql = IF(@exists = 0, 'ALTER TABLE messages ADD INDEX idx_messages_deleted_at (deleted_at)', 'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- flows.deleted_at
SELECT COUNT(1) INTO @exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'flows' AND COLUMN_NAME = 'deleted_at';
SET @sql = IF(@exists = 0, 'ALTER TABLE flows ADD COLUMN deleted_at DATETIME NULL', 'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT COUNT(1) INTO @exists FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'flows' AND INDEX_NAME = 'idx_flows_deleted_at';
SET @sql = IF(@exists = 0, 'ALTER TABLE flows ADD INDEX idx_flows_deleted_at (deleted_at)', 'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- conversations.current_step
SELECT COUNT(1) INTO @exists FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'conversations' AND COLUMN_NAME = 'current_step';
SET @sql = IF(@exists = 0, 'ALTER TABLE conversations ADD COLUMN current_step INT DEFAULT 0', 'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT COUNT(1) INTO @exists FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'conversations' AND INDEX_NAME = 'idx_conversations_current_step';
SET @sql = IF(@exists = 0, 'ALTER TABLE conversations ADD INDEX idx_conversations_current_step (current_step)', 'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- conversation_transitions (create table if absent, no FK)
SELECT COUNT(1) INTO @exists FROM information_schema.TABLES WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'conversation_transitions';
SET @sql = IF(@exists = 0,
  'CREATE TABLE conversation_transitions (id BIGINT AUTO_INCREMENT PRIMARY KEY, conversation_id CHAR(36) NOT NULL, from_step INT NULL, to_step INT NULL, step_id VARCHAR(191) NULL, action VARCHAR(64) NOT NULL, payload JSON NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_ct_conversation (conversation_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
  'SELECT "exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optional: unique index on contacts.phone (careful on existing duplicates)
-- ALTER TABLE contacts ADD UNIQUE KEY uq_contacts_phone (phone);
