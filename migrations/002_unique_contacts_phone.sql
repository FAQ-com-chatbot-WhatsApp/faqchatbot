-- Migration 002: add UNIQUE constraint on contacts.phone
-- WARNING: Before applying, ensure there are no duplicate phone values in contacts table.
-- Run in development: docker exec -i whatsbot-db mysql -u botuser -ppwd123 botdb < migrations/002_unique_contacts_phone.sql

-- Example steps to clean duplicates (manual review recommended):
-- 1) Find duplicates:
-- SELECT phone, COUNT(*) as cnt FROM contacts GROUP BY phone HAVING cnt > 1;
-- 2) Resolve duplicates (keep one, merge data) before applying UNIQUE.

-- Add UNIQUE index (commented out by default). Uncomment to apply.
-- ALTER TABLE contacts ADD UNIQUE KEY uq_contacts_phone (phone);
