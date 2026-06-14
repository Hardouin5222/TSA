CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE TABLE IF NOT EXISTS payment_intents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    cart_id VARCHAR(100) NOT NULL,
    user_id VARCHAR(100),
    guest_session_id VARCHAR(100),
    provider VARCHAR(50) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    amount NUMERIC(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    item_snapshot JSONB NOT NULL,
    provider_reference VARCHAR(100) NOT NULL UNIQUE,
    checkout_url TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_payment_intents_cart_id ON payment_intents (cart_id);
CREATE INDEX IF NOT EXISTS idx_payment_intents_user_id ON payment_intents (user_id);
CREATE INDEX IF NOT EXISTS idx_payment_intents_guest_session_id ON payment_intents (guest_session_id);
CREATE INDEX IF NOT EXISTS idx_payment_intents_status ON payment_intents (status);
