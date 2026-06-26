-- ============================================================
-- UKMInvolve – RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

create extension if not exists "pgcrypto";

-- ---------- TABLES ----------
create table if not exists public.users (
    id uuid primary key default gen_random_uuid(),
    nama text not null,
    emel text not null unique,
    password_hash text not null,
    peranan text not null check (peranan in ('pelajar', 'penganjur', 'pentadbir')),
    matrik text,
    fakulti text,
    organisasi text,
    status text not null default 'aktif',
    mata integer not null default 0,
    created_at timestamptz not null default now()
);

create table if not exists public.kategori (
    id serial primary key,
    nama text not null unique,
    slug text not null unique,
    color text not null default '#5b8def',
    icon text not null default 'fa-layer-group',
    mata integer not null default 100,
    created_at timestamptz not null default now()
);

create table if not exists public.program (
    id serial primary key,
    nama text not null,
    tarikh date not null,
    masa time not null,
    lokasi text not null,
    kategori_id integer references public.kategori(id),
    kapasiti integer not null default 100,
    peserta_semasa integer not null default 0,
    penerangan text,
    poster_url text,
    gambar text default 'program1.jpg',
    mata integer not null default 100,
    rating numeric(2,1) not null default 4.5,
    contact_person text,
    deadline date,
    penganjur_id uuid references public.users(id),
    jenis_pendaftaran text not null default 'Peserta' check (jenis_pendaftaran in ('Peserta', 'Crew/AJK')),
    created_at timestamptz not null default now()
);

create table if not exists public.student_interests (
    id serial primary key,
    student_id uuid references public.users(id) on delete cascade,
    category_id integer references public.kategori(id) on delete cascade,
    created_at timestamptz not null default now(),
    unique(student_id, category_id)
);

create table if not exists public.program_objektif (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    teks text not null,
    sort_order integer not null default 0
);

create table if not exists public.program_keperluan (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    teks text not null,
    sort_order integer not null default 0
);

create table if not exists public.program_sesi (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    tarikh date,
    masa_mulai time,
    masa_tamat time,
    topik text not null
);

create table if not exists public.pendaftaran (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    pelajar_id uuid references public.users(id),
    nama text not null,
    no_matrik text not null,
    fakulti text not null,
    emel text not null,
    telefon text not null,
    alasan text not null,
    status text not null default 'Registered',
    jenis_pendaftaran text not null default 'Peserta' check (jenis_pendaftaran in ('Peserta', 'Crew/AJK')),
    tarikh_daftar timestamptz not null default now()
);

create table if not exists public.kehadiran (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    pelajar_id uuid references public.users(id) on delete cascade,
    status text not null check (status in ('Hadir', 'Tidak Hadir')),
    created_at timestamptz not null default now(),
    unique(program_id, pelajar_id)
);

create table if not exists public.maklum_balas (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    pelajar_id uuid references public.users(id),
    rating integer not null check (rating between 1 and 5),
    komen text,
    created_at timestamptz not null default now()
);

create table if not exists public.mata_peraturan (
    id serial primary key,
    title text not null unique,
    description text,
    icon text not null default 'fa-star',
    value integer not null default 0
);

create table if not exists public.level_thresholds (
    level integer primary key,
    name text not null,
    xp integer not null
);

create table if not exists public.rekod_mata (
    id serial primary key,
    student_id uuid not null references public.users(id) on delete cascade,
    activity_type text not null,
    points integer not null,
    program_id integer references public.program(id) on delete cascade,
    created_at timestamptz not null default now(),
    unique(student_id, activity_type, program_id)
);

create unique index if not exists idx_rekod_mata_interest 
on public.rekod_mata(student_id, activity_type) 
where program_id is null;

create table if not exists public.lencana (
    id serial primary key,
    nama text not null unique,
    kriteria text not null,
    syarat_nilai integer not null,
    gambar text not null,
    deskripsi text,
    created_at timestamptz not null default now()
);

create table if not exists public.lencana_pelajar (
    id serial primary key,
    student_id uuid not null references public.users(id) on delete cascade,
    lencana_id integer not null references public.lencana(id) on delete cascade,
    created_at timestamptz not null default now(),
    unique(student_id, lencana_id)
);

-- Ensure columns exist if tables were created in Sprints 1/2
alter table public.program add column if not exists jenis_pendaftaran text not null default 'Peserta' check (jenis_pendaftaran in ('Peserta', 'Crew/AJK'));
alter table public.program add column if not exists status text not null default 'Aktif' check (status in ('Aktif', 'Akan Datang', 'Selesai', 'Cancelled'));
alter table public.pendaftaran add column if not exists jenis_pendaftaran text not null default 'Peserta' check (jenis_pendaftaran in ('Peserta', 'Crew/AJK'));
alter table public.kategori add column if not exists status text not null default 'aktif' check (status in ('aktif', 'tidak aktif'));
alter table public.kategori add column if not exists mata integer not null default 100;
alter table public.mata_peraturan drop constraint if exists mata_peraturan_title_key;
alter table public.mata_peraturan add constraint mata_peraturan_title_key unique (title);

-- ---------- API ACCESS (required for anon key) ----------
grant usage on schema public to anon, authenticated, service_role;
grant all on all tables in schema public to anon, authenticated, service_role;
grant all on all sequences in schema public to anon, authenticated, service_role;
alter default privileges in schema public grant all on tables to anon, authenticated, service_role;
alter default privileges in schema public grant all on sequences to anon, authenticated, service_role;

-- ---------- ROW LEVEL SECURITY ----------
alter table public.users enable row level security;
alter table public.kategori enable row level security;
alter table public.program enable row level security;
alter table public.student_interests enable row level security;
alter table public.program_objektif enable row level security;
alter table public.program_keperluan enable row level security;
alter table public.program_sesi enable row level security;
alter table public.pendaftaran enable row level security;
alter table public.kehadiran enable row level security;
alter table public.maklum_balas enable row level security;
alter table public.mata_peraturan enable row level security;
alter table public.level_thresholds enable row level security;
alter table public.rekod_mata enable row level security;
alter table public.lencana enable row level security;
alter table public.lencana_pelajar enable row level security;

-- Drop and recreate policies
drop policy if exists "users_select" on public.users;
drop policy if exists "users_insert" on public.users;
drop policy if exists "users_update" on public.users;
create policy "users_select" on public.users for select using (true);
create policy "users_insert" on public.users for insert with check (true);
create policy "users_update" on public.users for update using (true);

drop policy if exists "kategori_select" on public.kategori;
create policy "kategori_select" on public.kategori for select using (true);

drop policy if exists "program_select" on public.program;
drop policy if exists "program_insert" on public.program;
drop policy if exists "program_update" on public.program;
create policy "program_select" on public.program for select using (true);
create policy "program_insert" on public.program for insert with check (true);
create policy "program_update" on public.program for update using (true);

drop policy if exists "student_interests_select" on public.student_interests;
drop policy if exists "student_interests_insert" on public.student_interests;
drop policy if exists "student_interests_delete" on public.student_interests;
create policy "student_interests_select" on public.student_interests for select using (true);
create policy "student_interests_insert" on public.student_interests for insert with check (true);
create policy "student_interests_delete" on public.student_interests for delete using (true);

drop policy if exists "program_objektif_select" on public.program_objektif;
create policy "program_objektif_select" on public.program_objektif for select using (true);

drop policy if exists "program_keperluan_select" on public.program_keperluan;
create policy "program_keperluan_select" on public.program_keperluan for select using (true);

drop policy if exists "program_sesi_select" on public.program_sesi;
create policy "program_sesi_select" on public.program_sesi for select using (true);

drop policy if exists "pendaftaran_select" on public.pendaftaran;
drop policy if exists "pendaftaran_insert" on public.pendaftaran;
drop policy if exists "pendaftaran_update" on public.pendaftaran;
create policy "pendaftaran_select" on public.pendaftaran for select using (true);
create policy "pendaftaran_insert" on public.pendaftaran for insert with check (true);
create policy "pendaftaran_update" on public.pendaftaran for update using (true);

drop policy if exists "kehadiran_select" on public.kehadiran;
drop policy if exists "kehadiran_insert" on public.kehadiran;
drop policy if exists "kehadiran_update" on public.kehadiran;
create policy "kehadiran_select" on public.kehadiran for select using (true);
create policy "kehadiran_insert" on public.kehadiran for insert with check (true);
create policy "kehadiran_update" on public.kehadiran for update using (true);

drop policy if exists "maklum_balas_select" on public.maklum_balas;
drop policy if exists "maklum_balas_insert" on public.maklum_balas;
create policy "maklum_balas_select" on public.maklum_balas for select using (true);
create policy "maklum_balas_insert" on public.maklum_balas for insert with check (true);

drop policy if exists "mata_peraturan_select" on public.mata_peraturan;
create policy "mata_peraturan_select" on public.mata_peraturan for select using (true);
drop policy if exists "mata_peraturan_update" on public.mata_peraturan;
create policy "mata_peraturan_update" on public.mata_peraturan for update using (true);

drop policy if exists "level_thresholds_select" on public.level_thresholds;
drop policy if exists "level_thresholds_update" on public.level_thresholds;
create policy "level_thresholds_select" on public.level_thresholds for select using (true);
create policy "level_thresholds_update" on public.level_thresholds for update using (true);

drop policy if exists "rekod_mata_select" on public.rekod_mata;
drop policy if exists "rekod_mata_insert" on public.rekod_mata;
drop policy if exists "rekod_mata_delete" on public.rekod_mata;
create policy "rekod_mata_select" on public.rekod_mata for select using (true);
create policy "rekod_mata_insert" on public.rekod_mata for insert with check (true);
create policy "rekod_mata_delete" on public.rekod_mata for delete using (true);

drop policy if exists "lencana_select" on public.lencana;
create policy "lencana_select" on public.lencana for select using (true);

drop policy if exists "lencana_pelajar_select" on public.lencana_pelajar;
drop policy if exists "lencana_pelajar_insert" on public.lencana_pelajar;
create policy "lencana_pelajar_select" on public.lencana_pelajar for select using (true);
create policy "lencana_pelajar_insert" on public.lencana_pelajar for insert with check (true);

-- ---------- SEED DATA ----------
insert into public.kategori (nama, slug, color, icon, mata) values
    ('Kepimpinan', 'kepimpinan', '#f59e0b', 'fa-trophy', 150),
    ('Teknologi & IT', 'teknologi', '#3b82f6', 'fa-code', 100),
    ('Keusahawanan', 'keusahawanan', '#10b981', 'fa-briefcase', 120),
    ('Khidmat Komuniti', 'komuniti', '#ef4444', 'fa-heart', 200),
    ('Seni & Budaya', 'seni', '#8b5cf6', 'fa-palette', 120),
    ('Sukan & Kesihatan', 'sukan', '#f97316', 'fa-dumbbell', 120),
    ('Akademik', 'akademik', '#6366f1', 'fa-graduation-cap', 100),
    ('Kerjaya', 'kerjaya', '#14b8a6', 'fa-chart-line', 100)
on conflict (slug) do nothing;

-- Upcoming UKM Programs (Date > 2026-06-16)
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


insert into public.mata_peraturan (title, description, icon, value) values
    ('Daftar Program', 'Mata diberi apabila pelajar mendaftar program.', 'fa-user-plus', 20),
    ('Hadir Program', 'Mata diberi selepas kehadiran disahkan.', 'fa-calendar-check', 100),
    ('Beri Maklum Balas', 'Mata diberi selepas pelajar menghantar maklum balas.', 'fa-comment-dots', 30),
    ('Lengkapkan Minat', 'Mata diberi selepas pelajar memilih minat.', 'fa-heart', 50),
    ('Penyertaan Crew/AJK', 'Mata diberi apabila pelajar menghadiri program sebagai Crew/AJK.', 'fa-hands-helping', 200)
on conflict (title) do nothing;

insert into public.level_thresholds (level, name, xp) values
    (1, 'New Explorer', 0),
    (2, 'Active Starter', 200),
    (3, 'Campus Explorer', 500),
    (4, 'Active Achiever', 800),
    (5, 'UKM Champion', 1200)
on conflict (level) do update set name = excluded.name, xp = excluded.xp;

insert into public.lencana (nama, kriteria, syarat_nilai, gambar, deskripsi) values
    ('Bronze', 'points', 200, 'fa-award', 'Kumpul sekurang-kurangnya 200 mata untuk lencana Gangsa.'),
    ('Silver', 'points', 500, 'fa-award', 'Kumpul sekurang-kurangnya 500 mata untuk lencana Perak.'),
    ('Gold', 'points', 1200, 'fa-award', 'Kumpul sekurang-kurangnya 1200 mata untuk lencana Emas.'),
    ('Explorer', 'activity_count', 3, 'fa-compass', 'Hadir sekurang-kurangnya 3 program untuk lencana Explorer.')
on conflict (nama) do update set kriteria = excluded.kriteria, syarat_nilai = excluded.syarat_nilai, gambar = excluded.gambar, deskripsi = excluded.deskripsi;

-- Function get_user_by_emel
create or replace function public.get_user_by_emel(p_emel text)
returns setof public.users
language sql
stable
security definer
set search_path = public
as $$
    select *
    from public.users
    where lower(emel) = lower(trim(p_emel))
    limit 1;
$$;

grant execute on function public.get_user_by_emel(text) to anon, authenticated, service_role;

-- Refresh API schema cache (fixes "table not in schema cache")
notify pgrst, 'reload schema';
