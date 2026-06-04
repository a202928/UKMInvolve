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
    title text not null,
    description text,
    icon text not null default 'fa-star',
    value integer not null default 0
);

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
alter table public.program_objektif enable row level security;
alter table public.program_keperluan enable row level security;
alter table public.program_sesi enable row level security;
alter table public.pendaftaran enable row level security;
alter table public.maklum_balas enable row level security;
alter table public.mata_peraturan enable row level security;

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

drop policy if exists "maklum_balas_select" on public.maklum_balas;
drop policy if exists "maklum_balas_insert" on public.maklum_balas;
create policy "maklum_balas_select" on public.maklum_balas for select using (true);
create policy "maklum_balas_insert" on public.maklum_balas for insert with check (true);

drop policy if exists "mata_peraturan_select" on public.mata_peraturan;
create policy "mata_peraturan_select" on public.mata_peraturan for select using (true);

-- ---------- SEED DATA ----------
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

-- Login lookup (bypasses RLS so PHP can read password_hash after signup)
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
