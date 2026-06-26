-- ============================================================
-- UKMInvolve Email Verification & OTP Setup
-- RUN THIS FILE IN SUPABASE SQL EDITOR
-- ============================================================

-- 1. Add verification and OTP limit columns to users table
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS verification_code text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS verification_code_expires_at timestamptz;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS otp_attempts integer NOT NULL DEFAULT 0;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS last_otp_sent_at timestamptz;

-- 2. Insert "Verified Member" badge into lencana table
INSERT INTO public.lencana (nama, kriteria, syarat_nilai, gambar, deskripsi)
VALUES ('Verified Member', 'email_verified', 1, 'fa-circle-check', 'Telah mengesahkan emel akaun UKMInvolve.')
ON CONFLICT (nama) DO UPDATE 
SET kriteria = excluded.kriteria,
    syarat_nilai = excluded.syarat_nilai,
    gambar = excluded.gambar,
    deskripsi = excluded.deskripsi;
