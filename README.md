# DocuFlow Cloud — Document Management SaaS

A complete, commercial, **multi-tenant Document Management SaaS** built with
**Core PHP 8 + MySQL** (PDO, no framework), **Bootstrap 5**, **Chart.js** and
vanilla JavaScript. Companies register, get an isolated workspace, subscribe
to a plan, invite employees, and manage documents with folders, versions,
sharing, permissions and approval workflows. A platform **Super Admin**
manages tenants, plans, coupons, payments, invoices and support.

![Design](docs/design-reference.png)

Brand: clean white UI with **navy `#14243e` / `#1e3a5f`** and **purple
`#6d28d9` / `#7c3aed`** accents, matching the supplied design references.

---

## Feature summary

**Public SaaS website** — Home (hero with dashboard preview, trusted
companies carousel, features, live platform statistics, folder showcase,
upload workflow, collaboration & approval sections, security band,
testimonials, pricing with monthly/yearly toggle, integrations, FAQ,
newsletter), About, Features, Pricing, Solutions, Industries, Integrations,
Security, Blog + article, Contact (creates a support ticket), Login,
Register, Forgot Password, Privacy, Terms.

**SaaS platform (mandatory architecture)**
- **Multi-tenant**: unique tenant slug, `tenant_id` isolation on every
  business table, per-tenant branding (logo + brand color), tenant
  suspension/reactivation, tenant-aware authorization on *every* protected
  operation and file operation.
- **Auth & security**: registration with email verification, login/logout,
  secure password reset tokens, bcrypt hashing, hardened sessions, CSRF,
  XSS escaping, PDO prepared statements, login rate limiting (5/15 min),
  RBAC roles + permission matrix, employee invitations with expiring tokens,
  audit logs.
- **Subscriptions & billing**: Free / Starter / Professional / Business /
  Enterprise, monthly + yearly, 14-day trials, upgrade/downgrade/cancel/
  renew, coupons (`WELCOME10` = 10%), invoices, payment history, automatic
  expiry with a 7-day grace period, and enforced plan limits (storage, users,
  max file size, teams, version history). **Payments are recorded as
  `pending` and are only marked successful after verified confirmation**
  (gateway verification hook or explicit Super Admin verification) — never
  automatically.
- **Super Admin**: totals computed from live SQL (tenants active/suspended,
  users, active/expired subscriptions, MRR, ARR, transactions, storage),
  plan & coupon CRUD, tenant suspend/reactivate, user management, payment
  verification, invoices, support tickets with replies, announcements,
  platform settings, activity logs, usage analytics.
- **Tenant workspace**: overview with charts, file manager (grid/list,
  folder tree), global search with filters/sort/pagination, drag-and-drop
  multi-file uploads, categories & tags, document preview (PDF/image/text),
  permission-checked downloads, version history + restore, comments,
  share links with expiring tokens, per-user/per-team permissions,
  approval workflows (request → decide → notify), teams, user invitations,
  activity timeline, audit logs, storage manager with trash + purge,
  reports with CSV export and print-to-PDF, settings, profile, subscription
  & printable invoices.
- **File security**: extension allow-list + real MIME sniffing, size limits,
  randomized stored filenames, **private storage outside the web root**
  (`storage/private`), path-traversal-proof downloads, expiring share tokens.

## Tech stack

HTML5 · CSS3 · JavaScript · Bootstrap 5 · Bootstrap Icons · Chart.js ·
Core PHP 8.1+ · MySQL 8 · PDO. No Laravel/React/Vue/Node.

## Project structure

```
app/            Controllers, Models, Services, Middleware, Helpers
config/         app.php, database.php (env-driven)
public/         Web root: index.php (front controller), assets/, router.php
resources/views website / auth / tenant / super / partials / errors
routes/         web.php — tiny method+path router
database/       database.sql (schema + seeds), install.php (CLI installer)
storage/        private/ (documents — outside web root), uploads/ (logos),
                logs/, backups/
docs/           SECURITY.md, API.md, DEPLOYMENT.md
vercel-demo/    Static HTML/CSS/JS showcase deployed to Vercel
```

## Installation

Requirements: PHP 8.1+ (with `pdo_mysql`, `fileinfo`), MySQL 8, a web server
whose document root can point at `public/`.

```bash
# 1) Configure environment
cp .env.example .env        # set DB credentials + APP_URL

# 2) Create schema + seed data (choose one)
mysql -u root -p < database/database.sql
# …or, with DB credentials in .env:
php database/install.php

# 3) Run locally
php -S localhost:8000 -t public public/router.php
# → http://localhost:8000
```

The installer also writes the three demo document files into
`storage/private/` so seeded documents preview/download correctly.

## Demo credentials

All demo passwords are **`password`** (bcrypt-hashed in the seed data).

| Role | Email | Lands on |
|---|---|---|
| Super Admin | `super@docuflow.test` | `/super-admin/dashboard` |
| Company Admin | `admin@demo.com` | `/admin/dashboard` |
| Manager | `manager@demo.com` | `/manager/dashboard` |
| Member | `member@demo.com` | `/employee/dashboard` |
| Viewer | `viewer@demo.com` | `/employee/dashboard` |

Coupon: **`WELCOME10`** (10% off). Demo tenant: *Demo Company Inc*
(Professional plan, active) with sample folders, documents, teams, a paid
invoice and an open support ticket.

## Upgrade — role dashboard trees, AJAX file manager & workflows (2026-11-06)

The app was upgraded in place (same branding, layouts and design system —
see `UPGRADE-SUMMARY.md` for the full report):

- **Separate dashboard trees per role**, each with its own navigation and
  server-side permission checks on every page and document operation:
  `super-admin/` (dashboard, organizations, users, plans, subscriptions,
  payments, storage, reports, settings), `admin/` (dashboard, files,
  folders, users, teams, categories, sharing, approvals, versions, storage,
  audit-logs, reports, settings), `manager/` (team dashboard, team files,
  approvals, notifications) and `employee/` (personal dashboard, my files,
  approvals, notifications). Sign-in lands each role in its tree; the
  classic `/app` and `/super` consoles remain fully working.
- **Real JavaScript layer** (`public/assets/js/docuflow.js`): AJAX file
  manager with drag-and-drop uploads and per-file progress, bulk
  select/move/delete/restore/archive/favourite/download/share, grid/list
  toggle persisted per browser, live search + sorting + filters,
  breadcrumbs, toasts and confirm dialogs.
- **JSON API** (`api/*`, session + CSRF protected, tenant-scoped) returning
  real aggregates for Chart.js dashboards: totals, storage, monthly
  uploads, storage growth, user activity, categories, team performance and
  approval statistics — with date/user/category filters. See `docs/API.md`.
- **Completed modules:** restore-from-trash; departments; document expiry
  dates; custom key/value properties; favourites; password-protected share
  links with a public unlock page and a share access log (views, downloads,
  failed unlocks); multi-stage approval workflows with per-department
  templates and approve / reject / request-changes decisions; full version
  history preservation with metadata comparison; CSV + printable reports
  (documents, activity, users, sharing, approvals).
- **Database:** non-destructive migrations in `database/migrations/`
  (001 favourites, 002 share passwords + access logs, 003 approval
  workflows, 004 departments + document metadata — apply 004 before 003 on
  existing installs). Fresh installs get everything from
  `database/database.sql` automatically. Demo seeds/logins unchanged, plus
  a seeded password-protected share link (password `password`) and a
  two-stage review workflow with a pending request.
- **Bug fixed:** the router's `route()` helper rejected the Closure
  handlers used for static pages (TypeError under strict types) — handler
  type widened to `array|Closure`.
- **Runtime-verified:** the full smoke flow below was executed against
  MariaDB 10.11 + the PHP built-in server (install, per-role logins,
  upload/download, approvals, share unlock, payment verification,
  migrations) — six pre-existing runtime-fatal bugs in the original
  build were found and fixed in the process. Full list and results:
  `UPGRADE-SUMMARY.md` §6–7.

## Testing

```bash
# Syntax-check every PHP file
find . -name '*.php' -exec php -l {} \;

# Smoke flow (with the dev server running)
# 1. Open / and /pricing — pricing toggle switches monthly/yearly.
# 2. Register a new company → follow the verification link → sign in.
# 3. Upload a PDF in /app/upload → preview → create a share link →
#    open it in a private window → revoke it.
# 4. /app/subscription → change plan → invoice = unpaid, payment = pending.
# 5. Sign in as Super Admin → Payments → Verify → invoice flips to paid.
# 6. Role trees: admin@demo.com → /admin/dashboard + /admin/files (drag &
#    drop upload, bulk-select files, star a favourite, open Trash and
#    restore); manager@demo.com → /manager/approvals (seeded two-stage
#    request is waiting); member@demo.com → /employee/dashboard.
# 7. Open the seeded share link /s/demotoken000000000000000000000000000000000000001
#    → password gate (password: password) → download; the view/download is
#    recorded under /admin/sharing.
```

## Deployment notes (important & honest)

- **The full application needs PHP + MySQL hosting.** Point the web root at
  `public/`, import `database/database.sql`, set `.env`. See
  `docs/DEPLOYMENT.md` for Apache/Nginx details and the backup procedure.
- **Vercel does not provide native PHP/MySQL hosting**, so the PHP app is
  *not* deployed to Vercel. The `vercel-demo/` folder is a polished **static
  showcase** (landing page + dashboard UI with mock data) that *is* deployed
  to Vercel as the live demo, following the recommended split architecture:
  marketing/demo on Vercel, application on a PHP-compatible host.
- Never commit real credentials: `.env` is git-ignored; only
  `.env.example` placeholders ship in the repo.

## Documentation

- `docs/SECURITY.md` — isolation, auth, file security, billing integrity.
- `docs/API.md` — REST API contract + webhook events.
- `docs/DEPLOYMENT.md` — hosting, Vercel split, backup/restore, checklist.

## License

MIT — see `LICENSE`.
