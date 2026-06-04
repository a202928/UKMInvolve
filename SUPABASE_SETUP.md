# UKMInvolve – Supabase Setup

## 1. Configure `.env`

```bash
copy .env.example .env
```

Edit `.env`:

```env
SUPABASE_URL=https://YOUR_REF.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

Get values from **Supabase Dashboard → Project Settings → API**:
- **Project URL** → `SUPABASE_URL`
- **anon public** key → `SUPABASE_ANON_KEY`

Verify loading: `http://localhost/UKMInvolve_2/config/check_env.php`

## 2. Create tables (required – fixes "table not in schema cache")

Run **one file** in **SQL Editor**:

**`supabase/install_all.sql`** (creates tables, grants, policies, reloads API cache)

Or separately: `schema.sql` then `policies.sql`.

Verify: `http://localhost/UKMInvolve_2/config/check_tables.php` — all tables should show `"exists": true`.

## 3. Seed demo users

```
http://localhost/UKMInvolve_2/supabase/seed_users.php
```

Password: `password123`

| Role | Email |
|------|-------|
| pelajar | faiz@ukm.edu.my |
| penganjur | siti.aminah@ukm.edu.my |
| pentadbir | pentadbir@ukm.edu.my |

## 4. Direct PostgreSQL (optional)

**Not required.** The app uses Supabase REST API via `SUPABASE_URL` + `SUPABASE_ANON_KEY`.

If you want direct SQL from PHP:
1. Enable `extension=pdo_pgsql` in `php.ini`
2. Add to `.env`:
   ```
   DATABASE_URL=postgresql://postgres.[ref]:YOUR_DB_PASSWORD@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres
   ```
3. **Database password**: Dashboard → Project Settings → **Database** → Database password

## 5. Enable cURL

In `php.ini`: `extension=curl` — restart Apache.
