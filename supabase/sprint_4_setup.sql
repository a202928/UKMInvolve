-- ============================================================
-- UKMInvolve – RUN THIS ENTIRE FILE IN SUPABASE SQL EDITOR
-- Dashboard → SQL → New query → Paste → Run
-- ============================================================

-- 1. Create level_thresholds table if not exists
create table if not exists public.level_thresholds (
    level integer primary key,
    name text not null,
    xp integer not null
);

-- Enable RLS and create policies for level_thresholds
alter table public.level_thresholds enable row level security;
drop policy if exists "level_thresholds_select" on public.level_thresholds;
drop policy if exists "level_thresholds_update" on public.level_thresholds;
create policy "level_thresholds_select" on public.level_thresholds for select using (true);
create policy "level_thresholds_update" on public.level_thresholds for update using (true);

-- Grant privileges for level_thresholds
grant all on public.level_thresholds to anon, authenticated, service_role;

-- Seed levels if empty
insert into public.level_thresholds (level, name, xp) values
    (1, 'New Explorer', 0),
    (2, 'Active Starter', 200),
    (3, 'Campus Explorer', 500),
    (4, 'Active Achiever', 800),
    (5, 'UKM Champion', 1200)
on conflict (level) do update set name = excluded.name, xp = excluded.xp;


-- 2. Create rekod_mata table if not exists
create table if not exists public.rekod_mata (
    id serial primary key,
    student_id uuid not null references public.users(id) on delete cascade,
    activity_type text not null,
    points integer not null,
    program_id integer references public.program(id) on delete cascade,
    created_at timestamptz not null default now(),
    unique(student_id, activity_type, program_id)
);

-- Enable RLS and create policies for rekod_mata
alter table public.rekod_mata enable row level security;
drop policy if exists "rekod_mata_select" on public.rekod_mata;
drop policy if exists "rekod_mata_insert" on public.rekod_mata;
drop policy if exists "rekod_mata_delete" on public.rekod_mata;
create policy "rekod_mata_select" on public.rekod_mata for select using (true);
create policy "rekod_mata_insert" on public.rekod_mata for insert with check (true);
create policy "rekod_mata_delete" on public.rekod_mata for delete using (true);

-- Grant privileges for rekod_mata
grant all on public.rekod_mata to anon, authenticated, service_role;
grant all on sequence public.rekod_mata_id_seq to anon, authenticated, service_role;

-- Unique conditional index for interest where program_id is null
create unique index if not exists idx_rekod_mata_interest 
on public.rekod_mata(student_id, activity_type) 
where program_id is null;


-- 3. Create lencana table if not exists
create table if not exists public.lencana (
    id serial primary key,
    nama text not null unique,
    kriteria text not null, -- 'points' or 'activity_count'
    syarat_nilai integer not null,
    gambar text not null, -- FontAwesome class name, e.g., 'fa-award'
    deskripsi text,
    created_at timestamptz not null default now()
);

-- Enable RLS and create policies for lencana
alter table public.lencana enable row level security;
drop policy if exists "lencana_select" on public.lencana;
create policy "lencana_select" on public.lencana for select using (true);

-- Grant privileges for lencana
grant all on public.lencana to anon, authenticated, service_role;
grant all on sequence public.lencana_id_seq to anon, authenticated, service_role;

-- Seed default badges
insert into public.lencana (nama, kriteria, syarat_nilai, gambar, deskripsi) values
    ('Bronze', 'points', 200, 'fa-award', 'Kumpul sekurang-kurangnya 200 mata untuk lencana Gangsa.'),
    ('Silver', 'points', 500, 'fa-award', 'Kumpul sekurang-kurangnya 500 mata untuk lencana Perak.'),
    ('Gold', 'points', 1200, 'fa-award', 'Kumpul sekurang-kurangnya 1200 mata untuk lencana Emas.'),
    ('Explorer', 'activity_count', 3, 'fa-compass', 'Hadir sekurang-kurangnya 3 program untuk lencana Explorer.')
on conflict (nama) do update set kriteria = excluded.kriteria, syarat_nilai = excluded.syarat_nilai, gambar = excluded.gambar, deskripsi = excluded.deskripsi;


-- 4. Create lencana_pelajar table if not exists
create table if not exists public.lencana_pelajar (
    id serial primary key,
    student_id uuid not null references public.users(id) on delete cascade,
    lencana_id integer not null references public.lencana(id) on delete cascade,
    created_at timestamptz not null default now(),
    unique(student_id, lencana_id)
);

-- Enable RLS and create policies for lencana_pelajar
alter table public.lencana_pelajar enable row level security;
drop policy if exists "lencana_pelajar_select" on public.lencana_pelajar;
drop policy if exists "lencana_pelajar_insert" on public.lencana_pelajar;
create policy "lencana_pelajar_select" on public.lencana_pelajar for select using (true);
create policy "lencana_pelajar_insert" on public.lencana_pelajar for insert with check (true);

-- Grant privileges for lencana_pelajar
grant all on public.lencana_pelajar to anon, authenticated, service_role;
grant all on sequence public.lencana_pelajar_id_seq to anon, authenticated, service_role;


-- 5. Seed Crew/AJK rule in mata_peraturan if not exists
alter table public.mata_peraturan drop constraint if exists mata_peraturan_title_key;
alter table public.mata_peraturan add constraint mata_peraturan_title_key unique (title);

insert into public.mata_peraturan (title, description, icon, value) values
    ('Penyertaan Crew/AJK', 'Mata diberi apabila pelajar menghadiri program sebagai Crew/AJK.', 'fa-hands-helping', 200)
on conflict (title) do nothing;

-- 6. Add update policy for mata_peraturan to allow admin saves
drop policy if exists "mata_peraturan_update" on public.mata_peraturan;
create policy "mata_peraturan_update" on public.mata_peraturan for update using (true);
grant all on public.mata_peraturan to anon, authenticated, service_role;
grant all on sequence public.mata_peraturan_id_seq to anon, authenticated, service_role;

-- 7. Reload schema cache
notify pgrst, 'reload schema';
