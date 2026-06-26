-- ============================================================
-- UKMInvolve – RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Add Profile Columns to users table
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS avatar_url text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS bio text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS no_telefon text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS tahun_pengajian integer;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS kolej text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS social_links jsonb;

COMMENT ON COLUMN public.users.avatar_url IS 'Profile picture URL or organization logo path';
COMMENT ON COLUMN public.users.bio IS 'Student bio or organization description';
COMMENT ON COLUMN public.users.no_telefon IS 'Contact/phone number';
COMMENT ON COLUMN public.users.tahun_pengajian IS 'Student year of study';
COMMENT ON COLUMN public.users.kolej IS 'Student college';
COMMENT ON COLUMN public.users.social_links IS 'Social media links for organizers';

-- 2. Create saved_events table for persistent bookmarks
CREATE TABLE IF NOT EXISTS public.saved_events (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    program_id INTEGER NOT NULL REFERENCES public.program(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(user_id, program_id)
);

-- Enable RLS and create policies for saved_events
ALTER TABLE public.saved_events ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "saved_events_select" ON public.saved_events;
DROP POLICY IF EXISTS "saved_events_insert" ON public.saved_events;
DROP POLICY IF EXISTS "saved_events_delete" ON public.saved_events;

CREATE POLICY "saved_events_select" ON public.saved_events FOR SELECT USING (true);
CREATE POLICY "saved_events_insert" ON public.saved_events FOR INSERT WITH CHECK (true);
CREATE POLICY "saved_events_delete" ON public.saved_events FOR DELETE USING (true);

-- Grant privileges for saved_events
GRANT ALL ON public.saved_events TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.saved_events_id_seq TO anon, authenticated, service_role;

-- 3. Reload Schema Cache
NOTIFY pgrst, 'reload schema';
