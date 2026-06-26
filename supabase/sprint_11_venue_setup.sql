-- Sprint 11: Venue Maps & Navigation System Setup
-- Run in Supabase SQL Editor (Dashboard -> SQL Editor -> New Query)

ALTER TABLE public.program ADD COLUMN IF NOT EXISTS venue_type text DEFAULT 'physical' CHECK (venue_type IN ('physical', 'online', 'hybrid'));
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS venue_name text;
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS venue_address text;
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS google_maps_link text;
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS latitude numeric;
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS longitude numeric;
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS meeting_link text;
ALTER TABLE public.program ADD COLUMN IF NOT EXISTS platform text;
