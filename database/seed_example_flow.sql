-- Flow de exemplo para testes
-- Executar: docker exec -i whatsbot-db mysql -u botuser -ppwd123 botdb < database/seed_example_flow.sql

-- Gerar um flow ativo com UUID e definition JSON mínima
INSERT INTO flows (id, name, version, status, description, definition, created_at, updated_at)
VALUES (UUID(), 'FAQ Suporte Técnico', 1, 'active', 'Fluxo conversacional para dúvidas técnicas frequentes', '{"steps": []}', NOW(), NOW());

-- Verificar
SELECT id, name, status FROM flows WHERE name='FAQ Suporte Técnico' ORDER BY created_at DESC LIMIT 1;
