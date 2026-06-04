-- Run in Supabase SQL Editor if signup works but login says "Emel atau kata laluan tidak sah"
-- Fixes login lookup when RLS blocks reading users via REST API

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

notify pgrst, 'reload schema';
