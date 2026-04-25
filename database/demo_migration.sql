-- Kydesk Helpdesk — Demo accounts migration
-- Run once to add demo columns to existing tenants table.

ALTER TABLE tenants
    ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS demo_expires_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS demo_plan VARCHAR(20) NULL;

CREATE INDEX IF NOT EXISTS idx_tenants_demo_expiry ON tenants (is_demo, demo_expires_at);
