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
    created_at timestamptz not null default now()
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
    status text not null default 'pending',
    tarikh_daftar timestamptz not null default now()
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
    title text not null,
    description text,
    icon text not null default 'fa-star',
    value integer not null default 0
);

-- Row Level Security – run supabase/policies.sql when using SUPABASE_ANON_KEY
alter table public.users enable row level security;
alter table public.kategori enable row level security;
alter table public.program enable row level security;
alter table public.program_objektif enable row level security;
alter table public.program_keperluan enable row level security;
alter table public.program_sesi enable row level security;
alter table public.pendaftaran enable row level security;
alter table public.maklum_balas enable row level security;
alter table public.mata_peraturan enable row level security;

-- Seed categories
insert into public.kategori (nama, slug, color, icon) values
    ('Kepimpinan', 'kepimpinan', '#f59e0b', 'fa-trophy'),
    ('Teknologi & IT', 'teknologi', '#3b82f6', 'fa-code'),
    ('Keusahawanan', 'keusahawanan', '#10b981', 'fa-briefcase'),
    ('Khidmat Komuniti', 'komuniti', '#ef4444', 'fa-heart'),
    ('Seni & Budaya', 'seni', '#8b5cf6', 'fa-palette'),
    ('Sukan & Kesihatan', 'sukan', '#f97316', 'fa-dumbbell'),
    ('Akademik', 'akademik', '#6366f1', 'fa-graduation-cap'),
    ('Kerjaya', 'kerjaya', '#14b8a6', 'fa-chart-line')
on conflict (slug) do nothing;

-- Demo users: run supabase/seed_users.php once after schema (uses PHP password_hash).

-- Seed programs
insert into public.program (nama, tarikh, masa, lokasi, kategori_id, kapasiti, peserta_semasa, penerangan, gambar, mata, rating, contact_person, deadline)
select
    'Workshop Kepimpinan Mahasiswa',
    '2026-01-25'::date,
    '09:00'::time,
    'Dewan Tun Canselor',
    k.id,
    100, 45,
    'Program latihan kepimpinan intensif untuk mahasiswa.',
    'program1.jpg', 120, 4.8,
    'Pn. Noraini (03-89215432)',
    '2026-01-20'::date
from public.kategori k where k.slug = 'kepimpinan'
on conflict do nothing;

insert into public.program (nama, tarikh, masa, lokasi, kategori_id, kapasiti, peserta_semasa, penerangan, gambar, mata, rating)
select
    'Seminar Inovasi Digital',
    '2026-01-28'::date,
    '14:00'::time,
    'Auditorium FSKTM',
    k.id,
    150, 120,
    'Seminar mengenai teknologi digital terkini.',
    'program2.jpg', 150, 4.7
from public.kategori k where k.slug = 'teknologi';

insert into public.program (nama, tarikh, masa, lokasi, kategori_id, kapasiti, peserta_semasa, penerangan, gambar, mata, rating)
select
    'Program Sukarelawan Komuniti',
    '2026-02-02'::date,
    '08:00'::time,
    'Komuniti Bangi',
    k.id,
    50, 30,
    'Program khidmat masyarakat di kawasan setempat.',
    'program3.jpg', 180, 4.9
from public.kategori k where k.slug = 'komuniti';

insert into public.mata_peraturan (title, description, icon, value) values
    ('Daftar Program', 'Mata diberi apabila pelajar mendaftar program.', 'fa-user-plus', 20),
    ('Hadir Program', 'Mata diberi selepas kehadiran disahkan.', 'fa-calendar-check', 100),
    ('Beri Maklum Balas', 'Mata diberi selepas pelajar menghantar maklum balas.', 'fa-comment-dots', 30),
    ('Lengkapkan Minat', 'Mata diberi selepas pelajar memilih minat.', 'fa-heart', 50)
on conflict do nothing;
