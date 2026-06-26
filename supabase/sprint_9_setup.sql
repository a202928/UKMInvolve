-- ============================================================
-- UKMInvolve Sprint 9 – Crew Recruitment System
-- RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Update program table constraint for Registration Type (jenis_pendaftaran)
ALTER TABLE public.program DROP CONSTRAINT IF EXISTS program_jenis_pendaftaran_check;
ALTER TABLE public.program ADD CONSTRAINT program_jenis_pendaftaran_check CHECK (jenis_pendaftaran IN ('Peserta', 'Crew/AJK', 'Peserta & Crew/AJK', 'Hebahan Sahaja'));

-- 2. Create program_crew_positions table
CREATE TABLE IF NOT EXISTS public.program_crew_positions (
    id SERIAL PRIMARY KEY,
    program_id INTEGER NOT NULL REFERENCES public.program(id) ON DELETE CASCADE,
    nama_jawatan TEXT NOT NULL,
    deskripsi TEXT NOT NULL,
    kuota INTEGER NOT NULL DEFAULT 1,
    mata_ganjaran INTEGER NOT NULL DEFAULT 100,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3. Create crew_applications table
CREATE TABLE IF NOT EXISTS public.crew_applications (
    id SERIAL PRIMARY KEY,
    program_id INTEGER NOT NULL REFERENCES public.program(id) ON DELETE CASCADE,
    pelajar_id UUID NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    position_id INTEGER NOT NULL REFERENCES public.program_crew_positions(id) ON DELETE CASCADE,
    status TEXT NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending', 'Accepted', 'Rejected', 'Completed')),
    alasan TEXT,
    applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    reviewed_at TIMESTAMPTZ,
    UNIQUE(program_id, pelajar_id)
);

-- 4. Setup RLS for program_crew_positions
ALTER TABLE public.program_crew_positions ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "crew_positions_select" ON public.program_crew_positions;
DROP POLICY IF EXISTS "crew_positions_insert" ON public.program_crew_positions;
DROP POLICY IF EXISTS "crew_positions_update" ON public.program_crew_positions;
DROP POLICY IF EXISTS "crew_positions_delete" ON public.program_crew_positions;

CREATE POLICY "crew_positions_select" ON public.program_crew_positions FOR SELECT USING (true);
CREATE POLICY "crew_positions_insert" ON public.program_crew_positions FOR INSERT WITH CHECK (true);
CREATE POLICY "crew_positions_update" ON public.program_crew_positions FOR UPDATE USING (true);
CREATE POLICY "crew_positions_delete" ON public.program_crew_positions FOR DELETE USING (true);

-- 5. Setup RLS for crew_applications
ALTER TABLE public.crew_applications ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "crew_applications_select" ON public.crew_applications;
DROP POLICY IF EXISTS "crew_applications_insert" ON public.crew_applications;
DROP POLICY IF EXISTS "crew_applications_update" ON public.crew_applications;
DROP POLICY IF EXISTS "crew_applications_delete" ON public.crew_applications;

CREATE POLICY "crew_applications_select" ON public.crew_applications FOR SELECT USING (true);
CREATE POLICY "crew_applications_insert" ON public.crew_applications FOR INSERT WITH CHECK (true);
CREATE POLICY "crew_applications_update" ON public.crew_applications FOR UPDATE USING (true);
CREATE POLICY "crew_applications_delete" ON public.crew_applications FOR DELETE USING (true);

-- 6. Grant privileges
GRANT ALL ON public.program_crew_positions TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.program_crew_positions_id_seq TO anon, authenticated, service_role;

GRANT ALL ON public.crew_applications TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.crew_applications_id_seq TO anon, authenticated, service_role;

-- 7. Reload Schema Cache
NOTIFY pgrst, 'reload schema';
