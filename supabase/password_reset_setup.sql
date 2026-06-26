-- ============================================================
-- UKMInvolve Password Reset Setup
-- RUN THIS FILE IN SUPABASE SQL EDITOR
-- ============================================================

-- Add password reset columns to users table
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS reset_token text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS reset_token_expires_at timestamptz;
