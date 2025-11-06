-- Esquema inicial para plataforma conversacional (compatível com MariaDB 10.8)
-- Charset e Collation alinhados ao Joomla
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS contacts (
  id CHAR(36) PRIMARY KEY,
  external_id VARCHAR(191) NULL,
  name VARCHAR(191) NULL,
  phone VARCHAR(32) NULL,
  email VARCHAR(191) NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_contacts_external (external_id),
  KEY idx_contacts_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS flows (
  id CHAR(36) PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  version INT NOT NULL DEFAULT 1,
  status ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
  description TEXT NULL,
  definition JSON NOT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_flows_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversations (
  id CHAR(36) PRIMARY KEY,
  contact_id CHAR(36) NOT NULL,
  flow_id CHAR(36) NULL,
  status ENUM('active','completed','abandoned') NOT NULL DEFAULT 'active',
  last_message_id CHAR(36) NULL,
  message_count INT NOT NULL DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_conversations_contact FOREIGN KEY (contact_id) REFERENCES contacts(id),
  CONSTRAINT fk_conversations_flow FOREIGN KEY (flow_id) REFERENCES flows(id),
  KEY idx_conversations_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id CHAR(36) PRIMARY KEY,
  conversation_id CHAR(36) NOT NULL,
  direction ENUM('in','out') NOT NULL,
  type ENUM('text','image','audio','video','document','interactive') NOT NULL DEFAULT 'text',
  content JSON NOT NULL,
  status ENUM('queued','sent','delivered','read','failed','processed') DEFAULT 'queued',
  error_code VARCHAR(64) NULL,
  error_message VARCHAR(512) NULL,
  sent_at DATETIME NULL,
  received_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id),
  KEY idx_messages_conversation (conversation_id),
  KEY idx_messages_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices adicionais críticos
CREATE INDEX IF NOT EXISTS idx_messages_direction ON messages(direction);
