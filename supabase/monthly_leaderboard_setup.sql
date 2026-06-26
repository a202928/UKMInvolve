-- ============================================================
-- UKMInvolve Monthly Leaderboard & Hall of Fame Setup
-- RUN THIS FILE IN SUPABASE SQL EDITOR
-- ============================================================

CREATE TABLE IF NOT EXISTS public.hall_of_fame (
    id serial PRIMARY KEY,
    year_month text NOT NULL, -- e.g. '2026-05'
    winner_type text NOT NULL, -- 'student', 'organizer', 'program'
    entity_id uuid REFERENCES public.users(id) ON DELETE SET NULL, -- refers to student or organizer
    entity_id_int integer, -- refers to program id
    score numeric(10,2) NOT NULL,
    meta_name text NOT NULL,
    meta_subtext text,
    meta_image text,
    created_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE(year_month, winner_type)
);

ALTER TABLE public.hall_of_fame ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "hall_of_fame_select" ON public.hall_of_fame;
DROP POLICY IF EXISTS "hall_of_fame_all" ON public.hall_of_fame;
CREATE POLICY "hall_of_fame_select" ON public.hall_of_fame FOR SELECT USING (true);
CREATE POLICY "hall_of_fame_all" ON public.hall_of_fame FOR ALL USING (true);
GRANT ALL ON public.hall_of_fame TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.hall_of_fame_id_seq TO anon, authenticated, service_role;
