-- ============================================================
-- UKMInvolve – SPRINT 13 ORGANIZER PROFILES & FEEDS SETUP
-- RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Add organizer_type column to public.users table if it doesn't exist
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS organizer_type text CHECK (organizer_type IN ('faculty', 'college', 'organization'));

-- 2. Create public.organizer_posts table
CREATE TABLE IF NOT EXISTS public.organizer_posts (
    id serial PRIMARY KEY,
    organizer_id uuid NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    title varchar(255) NOT NULL,
    content text NOT NULL,
    post_type varchar(50) DEFAULT 'post' CHECK (post_type IN ('post', 'announcement', 'recruitment')),
    created_at timestamptz NOT NULL DEFAULT now()
);

-- Enable RLS and create policies for organizer_posts
ALTER TABLE public.organizer_posts ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "posts_select" ON public.organizer_posts;
DROP POLICY IF EXISTS "posts_insert" ON public.organizer_posts;
DROP POLICY IF EXISTS "posts_update" ON public.organizer_posts;
DROP POLICY IF EXISTS "posts_delete" ON public.organizer_posts;

CREATE POLICY "posts_select" ON public.organizer_posts FOR SELECT USING (true);
CREATE POLICY "posts_insert" ON public.organizer_posts FOR INSERT WITH CHECK (true);
CREATE POLICY "posts_update" ON public.organizer_posts FOR UPDATE USING (true);
CREATE POLICY "posts_delete" ON public.organizer_posts FOR DELETE USING (true);

-- Grant privileges for organizer_posts
GRANT ALL ON public.organizer_posts TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.organizer_posts_id_seq TO anon, authenticated, service_role;

-- Reload Schema Cache
NOTIFY pgrst, 'reload schema';
