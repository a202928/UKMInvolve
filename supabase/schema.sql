-- UKMInvolve – run in Supabase SQL Editor (Dashboard → SQL → New query)

-- Extensions
create extension if not exists "pgcrypto";

-- Users
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

-- Categories
create table if not exists public.kategori (
    id serial primary key,
    nama text not null unique,
    slug text not null unique,
    color text not null default '#5b8def',
    icon text not null default 'fa-layer-group',
    mata integer not null default 100,
    created_at timestamptz not null default now()
);

-- Programs
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

-- Registrations
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

-- Attendance
create table if not exists public.kehadiran (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    pelajar_id uuid references public.users(id) on delete cascade,
    status text not null check (status in ('Hadir', 'Tidak Hadir')),
    created_at timestamptz not null default now(),
    unique(program_id, pelajar_id)
);

-- Feedback
create table if not exists public.maklum_balas (
    id serial primary key,
    program_id integer not null references public.program(id) on delete cascade,
    pelajar_id uuid references public.users(id),
    rating integer not null check (rating between 1 and 5),
    komen text,
    created_at timestamptz not null default now()
);

-- Points rules (admin)
create table if not exists public.mata_peraturan (
    id serial primary key,
    title text not null unique,
    description text,
    icon text not null default 'fa-star',
    value integer not null default 0
);

-- Level Thresholds
create table if not exists public.level_thresholds (
    level integer primary key,
    name text not null,
    xp integer not null
);

-- Point Records
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

-- Badges
create table if not exists public.lencana (
    id serial primary key,
    nama text not null unique,
    kriteria text not null,
    syarat_nilai integer not null,
    gambar text not null,
    deskripsi text,
    created_at timestamptz not null default now()
);

-- Student Badges
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

-- Row Level Security – run supabase/policies.sql when using SUPABASE_ANON_KEY
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

-- Seed categories
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

-- Seed programs (Date > 2026-06-16)
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

