-- ============================================================
-- UKMInvolve – RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Add Target Audience JSONB column to program table
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS target_audience jsonb DEFAULT '["ALL"]'::jsonb;
COMMENT ON COLUMN public.program.target_audience IS 'Array of target faculties/colleges, or ["ALL"] for all students';

-- 2. Create followed_organizers table for student-organizer follows
CREATE TABLE IF NOT EXISTS public.followed_organizers (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    organizer_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE(user_id, organizer_id)
);

-- Enable RLS and create policies for followed_organizers
ALTER TABLE public.followed_organizers ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "followed_select" ON public.followed_organizers;
DROP POLICY IF EXISTS "followed_insert" ON public.followed_organizers;
DROP POLICY IF EXISTS "followed_delete" ON public.followed_organizers;

CREATE POLICY "followed_select" ON public.followed_organizers FOR SELECT USING (true);
CREATE POLICY "followed_insert" ON public.followed_organizers FOR INSERT WITH CHECK (true);
CREATE POLICY "followed_delete" ON public.followed_organizers FOR DELETE USING (true);

-- Grant privileges for followed_organizers
GRANT ALL ON public.followed_organizers TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.followed_organizers_id_seq TO anon, authenticated, service_role;

-- 3. Reload Schema Cache
NOTIFY pgrst, 'reload schema';
