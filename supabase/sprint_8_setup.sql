-- ============================================================
-- UKMInvolve – RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Update program table constraint for Registration Type (jenis_pendaftaran)
ALTER TABLE public.program DROP CONSTRAINT IF EXISTS program_jenis_pendaftaran_check;
ALTER TABLE public.program ADD CONSTRAINT program_jenis_pendaftaran_check CHECK (jenis_pendaftaran IN ('Peserta', 'Crew/AJK', 'Hebahan Sahaja'));

-- 2. Ensure the 'mata' column exists in 'kategori' table
ALTER TABLE public.kategori ADD COLUMN IF NOT EXISTS mata INTEGER NOT NULL DEFAULT 100;

-- 3. Fix RLS policies for kategori to allow inserts/updates/deletes
ALTER TABLE public.kategori ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "kategori_select" ON public.kategori;
DROP POLICY IF EXISTS "kategori_insert" ON public.kategori;
DROP POLICY IF EXISTS "kategori_update" ON public.kategori;
DROP POLICY IF EXISTS "kategori_delete" ON public.kategori;

CREATE POLICY "kategori_select" ON public.kategori FOR SELECT USING (true);
CREATE POLICY "kategori_insert" ON public.kategori FOR INSERT WITH CHECK (true);
CREATE POLICY "kategori_update" ON public.kategori FOR UPDATE USING (true);
CREATE POLICY "kategori_delete" ON public.kategori FOR DELETE USING (true);

-- Grant privileges for kategori
GRANT ALL ON public.kategori TO anon, authenticated, service_role;
GRANT ALL ON SEQUENCE public.kategori_id_seq TO anon, authenticated, service_role;

-- 4. Reload Schema Cache for PostgREST
NOTIFY pgrst, 'reload schema';
