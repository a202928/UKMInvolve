-- ============================================================
-- UKMInvolve – SPRINT 10 SETUP
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Add Deadline and Contact Info to program table
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS deadline_date DATE;
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS deadline_time TIME;
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS contact_person VARCHAR(255);
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS contact_number VARCHAR(50);
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS contact_email VARCHAR(255);
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS whatsapp_link VARCHAR(255);
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS instagram_link VARCHAR(255);
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS telegram_link VARCHAR(255);

-- 2. Create program_announcements table
CREATE TABLE IF NOT EXISTS public.program_announcements (
    id SERIAL PRIMARY KEY,
    program_id BIGINT NOT NULL REFERENCES public.program(id) ON DELETE CASCADE,
    organizer_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    target_audience VARCHAR(50) DEFAULT 'All', -- 'All', 'Participant', 'Crew'
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Enable RLS and create policies for program_announcements
ALTER TABLE public.program_announcements ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "announcements_select" ON public.program_announcements;
DROP POLICY IF EXISTS "announcements_insert" ON public.program_announcements;
DROP POLICY IF EXISTS "announcements_delete" ON public.program_announcements;

CREATE POLICY "announcements_select" ON public.program_announcements FOR SELECT USING (true);
CREATE POLICY "announcements_insert" ON public.program_announcements FOR INSERT WITH CHECK (true);
CREATE POLICY "announcements_delete" ON public.program_announcements FOR DELETE USING (true);

-- 3. Create program_files table
CREATE TABLE IF NOT EXISTS public.program_files (
    id SERIAL PRIMARY KEY,
    program_id BIGINT NOT NULL REFERENCES public.program(id) ON DELETE CASCADE,
    organizer_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    file_url TEXT NOT NULL,
    target_audience VARCHAR(50) DEFAULT 'All',
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Enable RLS and create policies for program_files
ALTER TABLE public.program_files ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "files_select" ON public.program_files;
DROP POLICY IF EXISTS "files_insert" ON public.program_files;
DROP POLICY IF EXISTS "files_delete" ON public.program_files;

CREATE POLICY "files_select" ON public.program_files FOR SELECT USING (true);
CREATE POLICY "files_insert" ON public.program_files FOR INSERT WITH CHECK (true);
CREATE POLICY "files_delete" ON public.program_files FOR DELETE USING (true);

-- 4. Create program_discussions table
CREATE TABLE IF NOT EXISTS public.program_discussions (
    id SERIAL PRIMARY KEY,
    program_id BIGINT NOT NULL REFERENCES public.program(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Enable RLS and create policies for program_discussions
ALTER TABLE public.program_discussions ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "discussions_select" ON public.program_discussions;
DROP POLICY IF EXISTS "discussions_insert" ON public.program_discussions;
DROP POLICY IF EXISTS "discussions_delete" ON public.program_discussions;

CREATE POLICY "discussions_select" ON public.program_discussions FOR SELECT USING (true);
CREATE POLICY "discussions_insert" ON public.program_discussions FOR INSERT WITH CHECK (true);
CREATE POLICY "discussions_delete" ON public.program_discussions FOR DELETE USING (true);

-- 5. Create notifications table
CREATE TABLE IF NOT EXISTS public.notifications (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Enable RLS and create policies for notifications
ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "notifications_select" ON public.notifications;
DROP POLICY IF EXISTS "notifications_insert" ON public.notifications;
DROP POLICY IF EXISTS "notifications_update" ON public.notifications;
DROP POLICY IF EXISTS "notifications_delete" ON public.notifications;

CREATE POLICY "notifications_select" ON public.notifications FOR SELECT USING (true);
CREATE POLICY "notifications_insert" ON public.notifications FOR INSERT WITH CHECK (true);
CREATE POLICY "notifications_update" ON public.notifications FOR UPDATE USING (true);
CREATE POLICY "notifications_delete" ON public.notifications FOR DELETE USING (true);

-- Grant privileges
GRANT ALL ON public.program_announcements TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.program_announcements_id_seq TO anon, authenticated, service_role;

GRANT ALL ON public.program_files TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.program_files_id_seq TO anon, authenticated, service_role;

GRANT ALL ON public.program_discussions TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.program_discussions_id_seq TO anon, authenticated, service_role;

GRANT ALL ON public.notifications TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.notifications_id_seq TO anon, authenticated, service_role;

-- Reload Schema Cache
NOTIFY pgrst, 'reload schema';
