-- Sprint 12: Event Sharing & Friend Invitation System Setup
-- Run in Supabase SQL Editor (Dashboard -> SQL Editor -> New Query)

-- 1. Add shares_count to public.program if it doesn't exist
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS shares_count integer NOT NULL DEFAULT 0;

-- 2. Create public.event_shares table for tracking individual user shares
CREATE TABLE IF NOT EXISTS public.event_shares (
    id serial PRIMARY KEY,
    user_id uuid REFERENCES public.users(id) ON DELETE CASCADE,
    event_id integer REFERENCES public.program(id) ON DELETE CASCADE,
    share_count integer NOT NULL DEFAULT 0,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT unique_user_event_share UNIQUE (user_id, event_id)
);

-- Enable RLS and create policies for event_shares
ALTER TABLE public.event_shares ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "event_shares_select" ON public.event_shares;
DROP POLICY IF EXISTS "event_shares_insert" ON public.event_shares;
DROP POLICY IF EXISTS "event_shares_update" ON public.event_shares;

CREATE POLICY "event_shares_select" ON public.event_shares FOR SELECT USING (true);
CREATE POLICY "event_shares_insert" ON public.event_shares FOR INSERT WITH CHECK (true);
CREATE POLICY "event_shares_update" ON public.event_shares FOR UPDATE USING (true);

-- Grant privileges for event_shares
GRANT ALL ON public.event_shares TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.event_shares_id_seq TO anon, authenticated, service_role;

-- Reload PostgREST Schema Cache
NOTIFY pgrst, 'reload schema';
