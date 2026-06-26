-- ============================================================
-- UKMInvolve Gamification & Progression System Setup
-- RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Extend level_thresholds table with progression requirements
ALTER TABLE public.level_thresholds ADD COLUMN IF NOT EXISTS req_programs integer NOT NULL DEFAULT 0;
ALTER TABLE public.level_thresholds ADD COLUMN IF NOT EXISTS req_crew integer NOT NULL DEFAULT 0;
ALTER TABLE public.level_thresholds ADD COLUMN IF NOT EXISTS req_streak integer NOT NULL DEFAULT 0;

-- 2. Clean up existing level seed data and insert exact 4 progression levels
DELETE FROM public.level_thresholds WHERE level > 4;

INSERT INTO public.level_thresholds (level, name, xp, req_programs, req_crew, req_streak) VALUES
(1, 'Participant', 0, 0, 0, 0),
(2, 'Crew Member', 150, 5, 0, 0),
(3, 'MT (Majlis Tertinggi)', 500, 5, 5, 0),
(4, 'UKM Elite', 1000, 10, 8, 1)
ON CONFLICT (level) DO UPDATE SET 
    name = excluded.name, 
    xp = excluded.xp, 
    req_programs = excluded.req_programs, 
    req_crew = excluded.req_crew,
    req_streak = excluded.req_streak;

-- 3. Cache student level and streak fields on the users table for fast querying
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS level integer NOT NULL DEFAULT 1;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS streak integer NOT NULL DEFAULT 0;

-- 4. Create voucher_rewards table for admin reward management
CREATE TABLE IF NOT EXISTS public.voucher_rewards (
    id serial PRIMARY KEY,
    title text NOT NULL,
    code text NOT NULL,
    points_cost integer NOT NULL DEFAULT 0,
    is_claimed boolean NOT NULL DEFAULT false,
    claimed_by uuid REFERENCES public.users(id) ON DELETE SET NULL,
    claimed_at timestamptz
);

ALTER TABLE public.voucher_rewards ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "voucher_rewards_select" ON public.voucher_rewards;
DROP POLICY IF EXISTS "voucher_rewards_all" ON public.voucher_rewards;
CREATE POLICY "voucher_rewards_select" ON public.voucher_rewards FOR SELECT USING (true);
CREATE POLICY "voucher_rewards_all" ON public.voucher_rewards FOR ALL USING (true);
GRANT ALL ON public.voucher_rewards TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.voucher_rewards_id_seq TO anon, authenticated, service_role;

-- 5. Create cert_templates table for digital certificates
CREATE TABLE IF NOT EXISTS public.cert_templates (
    id serial PRIMARY KEY,
    title text NOT NULL,
    template_text text NOT NULL,
    background_url text,
    created_at timestamptz NOT NULL DEFAULT now()
);

ALTER TABLE public.cert_templates ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "cert_templates_select" ON public.cert_templates;
DROP POLICY IF EXISTS "cert_templates_all" ON public.cert_templates;
CREATE POLICY "cert_templates_select" ON public.cert_templates FOR SELECT USING (true);
CREATE POLICY "cert_templates_all" ON public.cert_templates FOR ALL USING (true);
GRANT ALL ON public.cert_templates TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.cert_templates_id_seq TO anon, authenticated, service_role;

-- Insert a default certificate template
INSERT INTO public.cert_templates (id, title, template_text) VALUES
(1, 'Active Student Excellence e-Certificate', 'Sijil ini dengan sukacitanya dianugerahkan kepada {name} (No. Matrik: {matric}) bagi mengiktiraf pencapaian cemerlang beliau sebagai ahli UKM Elite dalam Program UKMInvolve pada {date}. Sekalung penghargaan atas dedikasi dan penglibatan aktif dalam memperkasakan aktiviti pembangunan mahasiswa UKM.')
ON CONFLICT (id) DO NOTHING;

-- 6. Create student_certs table to track generated certificates
CREATE TABLE IF NOT EXISTS public.student_certs (
    id serial PRIMARY KEY,
    student_id uuid NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    template_id integer NOT NULL REFERENCES public.cert_templates(id) ON DELETE CASCADE,
    cert_code text NOT NULL UNIQUE,
    issued_at timestamptz NOT NULL DEFAULT now()
);

ALTER TABLE public.student_certs ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "student_certs_select" ON public.student_certs;
DROP POLICY IF EXISTS "student_certs_all" ON public.student_certs;
CREATE POLICY "student_certs_select" ON public.student_certs FOR SELECT USING (true);
CREATE POLICY "student_certs_all" ON public.student_certs FOR ALL USING (true);
GRANT ALL ON public.student_certs TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.student_certs_id_seq TO anon, authenticated, service_role;
