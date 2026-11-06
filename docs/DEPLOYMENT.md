# DocuFlow Cloud — Deployment Guide

## Architecture

```
Browser ──► Web server (docroot = public/)
              └─ Core PHP 8 app (app/, config/, resources/)
                    ├─ MySQL 8 (docuflow_cloud)
                    └─ storage/private (uploaded files, NOT web-accessible)
```

## Option A — Standard PHP hosting (full application)
1. Upload the project to your host (Apache/Nginx + PHP 8.1+ + MySQL 8).
2. Point the virtual host document root at `public/`.
3. Copy `.env.example` → `.env`, set real DB credentials and `APP_URL`.
4. Import the schema: `mysql -u USER -p < database/database.sql`
   or run `php database/install.php`.
5. Ensure `storage/` is writable by the web server (0770) and **not** under
   the document root.
6. Apache: enable `mod_rewrite` (a fallback `public/.htaccess` is included in
   most installs; alternatively route all requests to `public/index.php`).
   Nginx: `try_files $uri $uri/ /index.php?$query_string;` inside the
   `public/` root.
7. Force HTTPS. Change all demo passwords.

## Option B — Vercel (public marketing/demo front end)
**Important:** Vercel does not provide native conventional PHP/MySQL
hosting. Do **not** claim the Core PHP application runs on Vercel.

Supported split architecture:
- Deploy the static showcase from `vercel-demo/` to Vercel (it is a pure
  HTML/CSS/JS replica of the landing page + dashboard for demos/marketing).
- Host the real PHP application + MySQL on a PHP-compatible provider
  (Option A) and link "Sign in / Start trial" buttons to that host.

## Database backup & restore
- Backup: `mysqldump -u USER -p docuflow_cloud > storage/backups/db-$(date +%F).sql`
  plus a copy of `storage/private/`.
- Restore: `mysql -u USER -p docuflow_cloud < backup.sql`, then restore the
  matching `storage/private/` snapshot — files and metadata must be restored
  together.

## Verification checklist
- [ ] Landing page loads; pricing toggle works.
- [ ] Register a company → verification link → login.
- [ ] Upload a PDF in the tenant workspace; preview + download work.
- [ ] Create a share link; open it in a private window; revoke it.
- [ ] Super Admin: dashboard stats render from live queries.
- [ ] Change plan → invoice shows `unpaid`, payment `pending`; verify the
      payment in Super Admin → invoice flips to `paid`, plan activates.
