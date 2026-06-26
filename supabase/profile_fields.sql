-- Migration: Add profile columns to public.users table
-- Execute this script in your Supabase SQL Editor (Dashboard -> SQL Editor -> New Query)

ALTER TABLE public.users ADD COLUMN IF NOT EXISTS avatar_url text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS bio text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS no_telefon text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS tahun_pengajian integer;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS kolej text;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS social_links jsonb;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS kursus_pengajian text;

-- Comment for documentation
COMMENT ON COLUMN public.users.avatar_url IS 'Profile picture URL or organization logo path';
COMMENT ON COLUMN public.users.bio IS 'Student bio or organization description';
COMMENT ON COLUMN public.users.no_telefon IS 'Contact/phone number';
COMMENT ON COLUMN public.users.tahun_pengajian IS 'Student year of study';
COMMENT ON COLUMN public.users.kolej IS 'Student college';
COMMENT ON COLUMN public.users.social_links IS 'Social media links for organizers';
COMMENT ON COLUMN public.users.kursus_pengajian IS 'Student course of study';
