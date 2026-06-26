-- ============================================================
-- UKMInvolve – RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Create student_interests table if not exists
create table if not exists public.student_interests (
    id serial primary key,
    student_id uuid references public.users(id) on delete cascade,
    category_id integer references public.kategori(id) on delete cascade,
    created_at timestamptz not null default now(),
    unique(student_id, category_id)
);

-- Enable RLS and create policies for student_interests
alter table public.student_interests enable row level security;
drop policy if exists "student_interests_select" on public.student_interests;
drop policy if exists "student_interests_insert" on public.student_interests;
drop policy if exists "student_interests_delete" on public.student_interests;
create policy "student_interests_select" on public.student_interests for select using (true);
create policy "student_interests_insert" on public.student_interests for insert with check (true);
create policy "student_interests_delete" on public.student_interests for delete using (true);

-- Grant privileges for student_interests
grant all on public.student_interests to anon, authenticated, service_role;
grant all on sequence public.student_interests_id_seq to anon, authenticated, service_role;

-- 2. Create kehadiran table if not exists
create table if not exists public.kehadiran (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    pelajar_id uuid references public.users(id) on delete cascade,
    status text not null check (status in ('Hadir', 'Tidak Hadir')),
    created_at timestamptz not null default now(),
    unique(program_id, pelajar_id)
);

-- Enable RLS and create policies for kehadiran
alter table public.kehadiran enable row level security;
drop policy if exists "kehadiran_select" on public.kehadiran;
drop policy if exists "kehadiran_insert" on public.kehadiran;
drop policy if exists "kehadiran_update" on public.kehadiran;
create policy "kehadiran_select" on public.kehadiran for select using (true);
create policy "kehadiran_insert" on public.kehadiran for insert with check (true);
create policy "kehadiran_update" on public.kehadiran for update using (true);

-- Grant privileges for kehadiran
grant all on public.kehadiran to anon, authenticated, service_role;
grant all on sequence public.kehadiran_id_seq to anon, authenticated, service_role;

-- 3. Add jenis_pendaftaran field to public.program if not exists
alter table public.program add column if not exists jenis_pendaftaran text not null default 'Peserta' check (jenis_pendaftaran in ('Peserta', 'Crew/AJK'));

-- 4. Add jenis_pendaftaran field to public.pendaftaran if not exists
alter table public.pendaftaran add column if not exists jenis_pendaftaran text not null default 'Peserta' check (jenis_pendaftaran in ('Peserta', 'Crew/AJK'));

-- 5. Clear old seed programs to prevent duplicate keys or name conflicts
delete from public.program where nama in (
    'Workshop Kepimpinan Mahasiswa', 
    'Seminar Inovasi Digital', 
    'Program Sukarelawan Komuniti',
    'Bengkel Kepimpinan Mahasiswa',
    'Hari Terbuka FTSM'
);

-- 6. Seed realistic upcoming UKM programs (Date > 2026-06-16)
-- A. Seminar Inovasi Digital — Peserta
insert into public.program (nama, tarikh, masa, lokasi, kategori_id, kapasiti, peserta_semasa, penerangan, gambar, mata, rating, contact_person, deadline)
select
    'Seminar Inovasi Digital',
    '2026-07-15'::date,
    '09:00'::time,
    'Auditorium FSKTM, UKM Bangi',
    k.id,
    150, 0,
    'Seminar mengenai trend inovasi digital masa kini dan impak kecerdasan buatan terhadap graduan baharu.',
    'program2.jpg', 100, 4.8,
    'Dr. Faiz (03-89216091)',
    '2026-07-10'::date
from public.kategori k where k.slug = 'teknologi'
limit 1;

-- B. Program Sukarelawan Komuniti — Crew/AJK
insert into public.program (nama, tarikh, masa, lokasi, kategori_id, kapasiti, peserta_semasa, penerangan, gambar, mata, rating, contact_person, deadline)
select
    'Program Sukarelawan Komuniti',
    '2026-07-20'::date,
    '08:00'::time,
    'Kampung Sungai Merab, Bangi',
    k.id,
    30, 0,
    'Peluang menjadi sukarelawan/AJK bagi menguruskan kemasyarakatan luar bandar dan program tuisyen percuma.',
    'program3.jpg', 200, 4.9,
    'Urus Setia Sukarelawan (019-1234567)',
    '2026-07-16'::date
from public.kategori k where k.slug = 'komuniti'
limit 1;

-- C. Bengkel Kepimpinan Mahasiswa — Peserta
insert into public.program (nama, tarikh, masa, lokasi, kategori_id, kapasiti, peserta_semasa, penerangan, gambar, mata, rating, contact_person, deadline)
select
    'Bengkel Kepimpinan Mahasiswa',
    '2026-08-05'::date,
    '09:00'::time,
    'Dewan Tun Canselor, UKM Bangi',
    k.id,
    100, 0,
    'Latihan intensif kepimpinan, kemahiran berpasukan, dan strategi membuat keputusan untuk belia masa kini.',
    'program1.jpg', 150, 4.7,
    'Pusat Pembangunan Pelajar (03-89215321)',
    '2026-08-01'::date
from public.kategori k where k.slug = 'kepimpinan'
limit 1;

-- D. Hari Terbuka FTSM — Crew/AJK
insert into public.program (nama, tarikh, masa, lokasi, kategori_id, kapasiti, peserta_semasa, penerangan, gambar, mata, rating, contact_person, deadline)
select
    'Hari Terbuka FTSM',
    '2026-08-12'::date,
    '08:30'::time,
    'Fakulti Teknologi & Sains Maklumat, UKM Bangi',
    k.id,
    50, 0,
    'Menyertai jawatankuasa penganjur (AJK) bagi mengendalikan gerai pameran, pendaftaran pelawat, dan penyelarasan acara.',
    'program2.jpg', 120, 4.6,
    'Sekretariat FTSM (ftsm@ukm.edu.my)',
    '2026-08-08'::date
from public.kategori k where k.slug = 'teknologi'
limit 1;

-- Set jenis_pendaftaran for Crew/AJK programs dynamically to avoid parse-time errors
DO $$
BEGIN
    EXECUTE 'update public.program set jenis_pendaftaran = ''Crew/AJK'' where nama in (''Program Sukarelawan Komuniti'', ''Hari Terbuka FTSM'')';
END $$;

-- 7. Reload schema cache
notify pgrst, 'reload schema';

