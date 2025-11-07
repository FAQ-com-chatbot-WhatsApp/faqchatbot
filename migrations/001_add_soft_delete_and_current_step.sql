-- Migration 001: add soft-delete columns and current_step and conversation_transitions table
-- Run in development: docker exec -i whatsbot-db mysql -u botuser -ppwd123 botdb < migrations/001_add_soft_delete_and_current_step.sql

ALTER TABLE contacts ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL, ADD INDEX idx_contacts_deleted_at (deleted_at);
ALTER TABLE messages ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL, ADD INDEX idx_messages_deleted_at (deleted_at);
ALTER TABLE flows ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL, ADD INDEX idx_flows_deleted_at (deleted_at);

ALTER TABLE conversations ADD COLUMN IF NOT EXISTS current_step INT DEFAULT 0, ADD INDEX idx_conversations_current_step (current_step);

CREATE TABLE IF NOT EXISTS conversation_transitions (
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

-- Optional: unique index on contacts.phone (careful on existing duplicates)
-- ALTER TABLE contacts ADD UNIQUE KEY uq_contacts_phone (phone);
