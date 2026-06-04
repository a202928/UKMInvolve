-- Run AFTER schema.sql if using SUPABASE_ANON_KEY from PHP
-- Allows PostgREST access for the UKMInvolve PHP app

-- Users (login reads emel + password_hash on server – tighten in production)
create policy "users_select" on public.users for select using (true);
create policy "users_insert" on public.users for insert with check (true);
create policy "users_update" on public.users for update using (true);

-- Categories & programs
create policy "kategori_select" on public.kategori for select using (true);
create policy "program_select" on public.program for select using (true);
create policy "program_insert" on public.program for insert with check (true);
create policy "program_update" on public.program for update using (true);

create policy "program_objektif_select" on public.program_objektif for select using (true);
create policy "program_keperluan_select" on public.program_keperluan for select using (true);
create policy "program_sesi_select" on public.program_sesi for select using (true);

-- Registrations & feedback
create policy "pendaftaran_select" on public.pendaftaran for select using (true);
create policy "pendaftaran_insert" on public.pendaftaran for insert with check (true);
create policy "pendaftaran_update" on public.pendaftaran for update using (true);

create policy "maklum_balas_select" on public.maklum_balas for select using (true);
create policy "maklum_balas_insert" on public.maklum_balas for insert with check (true);

create policy "mata_peraturan_select" on public.mata_peraturan for select using (true);
