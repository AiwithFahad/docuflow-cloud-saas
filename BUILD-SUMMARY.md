# DocuFlow Cloud — Build Summary

**Product:** DocuFlow Cloud — Document Management SaaS (Project 04)
**Stack:** Core PHP 8 + MySQL (PDO, no framework) · Bootstrap 5 · Chart.js · Bootstrap Icons
**Location:** `~/workspace/saas-products-batch2/docuflow-cloud-saas/`

## Counts (verified at build time)

| Metric | Value |
|---|---|
| Total files (incl. this summary) | **107** |
| PHP files | **80** |
| PHP lint (`php -l`, PHP 8.3) | **80 / 80 pass, 0 failures** |
| View templates (PHP) | 57 |
| MySQL tables in `database/database.sql` | **35** |
| Static demo files (`vercel-demo/`) | 4 (index, dashboard, CSS, JS) |

## What was built

- **Public website** (15 pages): home with hero + CSS dashboard preview,
  trusted-companies marquee, features, real platform statistics, folder
  showcase, 4-step upload workflow, collaboration/approvals, security band,
  testimonial carousel, pricing with monthly/yearly toggle, integrations,
  blog + article, FAQ, contact (writes support tickets), newsletter,
  privacy, terms.
- **Auth**: company registration (slug, trial activation, email
  verification), login with rate limiting (5 fails / 15 min), logout,
  hashed-token password reset, employee invitations, RBAC
  (super_admin / company_admin / manager / member / viewer + permission rows).
- **Tenant workspace**: overview charts, file manager (nested folder tree,
  grid/list), global search (name/category/tag, sort, pagination),
  drag-and-drop multi-file upload, preview (PDF/image/text), secure
  downloads, rename/move/copy/delete (soft-delete trash + purge), version
  history + restore with plan cap, comments, expiring share-token links,
  user/team permissions, approval workflows with notifications, teams,
  user management, activity timeline, audit logs, storage manager, reports
  with CSV export + print/PDF, settings with logo upload + branding,
  profile/password.
- **Super Admin**: live-computed totals (tenants, active/suspended, users,
  subscriptions active/expired, MRR, ARR, transactions, storage), revenue &
  plan-mix charts, plan CRUD, coupon CRUD, tenant suspend/reactivate, user
  enable/disable, payment verification (pending → paid only after verified
  confirmation), invoices, support tickets with replies, announcements,
  platform settings, activity logs, usage analytics.
- **Subscriptions**: Free / Starter / Professional / Business / Enterprise;
  monthly + yearly; trial; upgrade/downgrade/cancel/renew; coupons;
  invoices; expiry + 7-day grace; enforced limits for storage / users /
  file size / teams / version history.
- **Security**: tenant_id isolation everywhere, extension allow-list + MIME
  sniffing, randomized stored filenames, files in `storage/private/`
  (outside the `public/` web root), traversal-proof resolution, CSRF on all
  POSTs, prepared statements, output escaping, bcrypt password hashing.
- **`vercel-demo/`**: responsive static showcase (landing + dashboard with
  collapsible sidebar, folder tree, grid/list file manager, storage meter,
  charts, mock data) in the reference style — white, navy, purple.

## Database — 35 tables

Common SaaS (21): tenants, tenant_settings, users, roles, permissions,
role_permissions, login_attempts, password_resets, invitations,
subscription_plans, subscriptions, subscription_usage, coupons, invoices,
payments, notifications, support_tickets, activity_logs, audit_logs,
announcements, platform_settings, newsletter_subscribers.
Documents (11): teams, team_members, folders, categories, tags, documents,
document_versions, document_tags, document_permissions, document_shares,
approvals, approval_steps, comments — (13 with teams pair; all FK-linked,
indexed, tenant-scoped).
Seeds: 5 plans, roles/permissions, coupon, platform settings, announcement,
demo tenant, 5 users (one per role), active Professional subscription with a
verified paid invoice, 2 teams, 4 folders, 4 categories, 4 tags, 3 documents
(+ versions, tags, permissions, comments), notification, support ticket,
activity entries.

## Demo credentials (all passwords: `password`)

| Role | Email |
|---|---|
| Super Admin | super@docuflow.test |
| Company Admin | admin@demo.com |
| Manager | manager@demo.com |
| Member | member@demo.com |
| Viewer | viewer@demo.com |

Coupon: **WELCOME10** (10% off).

## Brand palette

- Navy: `#14243e` (deep) / `#1e3a5f` (primary)
- Purple: `#6d28d9` / `#7c3aed` (accent gradient)
- Surfaces: white `#ffffff`, app background `#f4f5fa` / site `#f7f8fc`
- Folder default `#f5b93f`, success `#15803d`, danger `#dc2626`

## Not done here (by instruction)

No `git`, GitHub, or Vercel commands were run — files only, ready for the
parent workflow to commit, push, and deploy `vercel-demo/`.

## Honesty note

Vercel cannot host Core PHP/MySQL natively; the PHP application targets a
PHP-compatible host (docroot `public/`), and `vercel-demo/` is the static
showcase for Vercel — exactly as documented in README.md and
docs/DEPLOYMENT.md.
