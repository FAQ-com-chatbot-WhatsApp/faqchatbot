-- Seed: sample data for development and smoke tests
-- Idempotent inserts: only insert when not exists

SET @flow_id := '6e253d66-5bcb-4f2b-93c3-618ef7d81477';
SET @flow_name := 'Flow Atualizado';

-- Insert flow if not exists
INSERT INTO flows (id, name, version, status, description, definition, created_by, created_at, updated_at)
SELECT @flow_id, @flow_name, 1, 'active', 'Flow para suporte técnico', JSON_OBJECT('steps', JSON_ARRAY(JSON_OBJECT('order',1,'type','message','message_id', NULL))), 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM flows WHERE id = @flow_id);

-- Insert a sample contact
SET @contact_phone := '+5511999000111';
SET @contact_name := 'Contato Teste';
SET @contact_id := '09c51d32-7c99-4d63-88b8-6d7cdc52e40f';

INSERT INTO contacts (id, name, phone, created_at, updated_at)
SELECT @contact_id, @contact_name, @contact_phone, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM contacts WHERE id = @contact_id OR phone = @contact_phone);

-- Optional: insert an example conversation if not exists
SET @conv_id := '65fb4050-ffdf-4810-b262-3d69936378f4';
INSERT INTO conversations (id, contact_id, flow_id, status, message_count, created_at, updated_at)
SELECT @conv_id, @contact_id, @flow_id, 'active', 0, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM conversations WHERE id = @conv_id);

-- Small message example
SET @msg_id := 'msg-sample-0000000000000000000001';
INSERT INTO messages (id, conversation_id, direction, type, content, status, created_at)
SELECT @msg_id, @conv_id, 'out', 'text', JSON_OBJECT('body','Bem-vindo ao Flow de Teste'), 'processed', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM messages WHERE id = @msg_id);

-- End of seed
