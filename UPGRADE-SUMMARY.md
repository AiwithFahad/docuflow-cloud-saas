# DocuFlow Cloud — Upgrade Summary (2026-11-06)

Upgrade performed **in place** on the existing application — no rebuild, no
redesign. Branding (navy `#14243e`/`#1e3a5f`, purple `#6d28d9`/`#7c3aed`),
logo, typography, homepage, sidebar/topbar shells, illustrations and
responsive layout are unchanged; all new pages reuse the existing
`.panel` / `.stat-card` / `.doc-card` design system. The classic `/app`
workspace and `/super` console keep working exactly as before.

Repo (unchanged by this task — files only, no git/GitHub/Vercel commands
were run): https://github.com/AiwithFahad/docuflow-cloud-saas
Live static showcase (unchanged deployment): https://docuflow-cloud-saas.vercel.app

---

## 1. Separate dashboard trees (role-specific, permission-checked)

Sign-in now lands each role in its own tree (`AuthController::roleHome()`).
Every tree controller enforces its role server-side, every query is
`tenant_id`-scoped, and every document read/write passes
`Document::userCanAccess()` (owner / company admin / explicit user-or-team
permission) — there is no file access without verified rights.

### super-admin/ (SuperAdminTreeController, platform owner only)
| Page | Contents |
|---|---|
| dashboard.php | Real totals (organizations, users, subscriptions, MRR/ARR, storage, pending payments, share links, tickets), revenue + plan-mix charts, recent tenants/payments |
| organizations.php | Tenant list with plan/subscription/users/documents/storage, search, suspend/reactivate |
| users.php | All users across tenants, search, enable/disable (not self) |
| plans.php | Plan CRUD (Free/Starter/Professional/Business/Enterprise tiers) + coupon CRUD |
| subscriptions.php | **New page**: all subscriptions with stored status + computed effective status (expiry/grace), status filters |
| payments.php | Payments with **verified-only** “Verify & activate” via `PaymentService::confirmPayment()`; mark-failed for pending |
| storage.php | **New page**: platform storage — per-tenant usage vs plan quota, trash totals, by-type, largest files |
| reports.php | Revenue/signups charts, subscription status, plan mix, top tenants; revenue CSV export; print/PDF |
| settings.php | Platform settings form |

### admin/ (AdminTreeController, company_admin only)
dashboard.php (live stats + Chart.js analytics with date/user/category
filters) · files.php (AJAX file manager) · folders.php (create/rename/move/
delete with counts) · users.php (roles/status + invitations with plan seat
limits) · teams.php (CRUD + members) · categories.php (categories, tags,
**departments**) · sharing.php (public links incl. password set/remove +
revoke, internal permissions, **share access log**) · approvals.php (inbox,
all requests with step timeline, request form, **workflow templates**) ·
versions.php (history, upload, restore, **metadata comparison**) ·
storage.php (usage, by type/user, largest, **trash restore + purge**) ·
audit-logs.php (audit + activity, entity filter) · reports.php (documents,
activity, user activity, sharing, approvals, team performance + 5 CSV
exports + print) · settings.php (branding, logo, sharing/approval defaults).

### manager/ (ManagerTreeController, manager role)
dashboard: my teams, team documents, **pending approvals (assigned tasks)**,
documents shared with my teams, team activity, recent team uploads ·
files (permission-filtered AJAX file manager) · approvals (inbox, my
requests, decision history, request form) · notifications centre.

### employee/ (EmployeeTreeController, member/viewer roles)
dashboard: my documents, recent accessible files, shared with me (with
rights), pending approvals (to decide + my requests), my folders,
notifications, my storage usage vs workspace · files · approvals ·
notifications centre.

## 2. Real JavaScript layer + JSON API

`public/assets/js/docuflow.js` (loaded by the existing shell on every
authenticated page): toast notifications, promise-based confirm/prompt
dialogs, favourite toggles, live stat refresh, Chart.js wiring with filter
forms, version-compare widget, and a full **file manager**:

- AJAX listing without reload; drag-and-drop upload with **per-file
  progress bars** (XHR) + upload button; bulk selection with bulk bar —
  move, delete, restore, purge, archive/unarchive, favourite, multi-file
  download, single-doc share-link creation (with optional password);
- grid/list toggle **persisted** in localStorage; live search (debounced),
  sorting (new/old/name/size/downloads), filters (status incl.
  favourites/recent/trash, category, tag, department); breadcrumb
  navigation updates; folder navigation; rename via API; no dummy buttons.

`app/Controllers/ApiController.php` — session + CSRF protected, tenant
scoped, super-admin rejected. Endpoints: `api/stats`, `api/charts`
(uploads, storage, activity, categories, teams, approvals — real SQL
aggregates with date/user/category filters), `api/documents` (permission
filtered listing), `api/documents/{id}`, `…/versions`, `api/documents/bulk`,
`…/favorite`, `…/rename`, `…/share`, `api/folders` (GET/POST), `api/upload`,
`api/search`, `api/notifications` (+read), `api/activity`. Full table in
`docs/API.md`.

## 3. Functional gaps completed

- **File manager:** upload/download/preview/rename/copy/move/delete +
  **restore-from-trash** (classic storage page, admin storage, API bulk),
  nested folders with rename/move (descendant-guarded), sorting/searching.
- **Organization:** categories, tags, **departments**, descriptions,
  owners, status, **expiry dates** (with expired/expiring badges),
  **custom key/value properties**, tags sync, advanced filtering,
  **favourites**, recent + archived smart views.
- **Sharing:** internal user/team permissions (view/download/edit),
  expiring links, revoke, **password-protected external links** (bcrypt,
  session unlock, 5-attempt throttle) with a public password page, and
  **sharing activity logs** (view/download/unlock_failed + IP/UA).
- **Approvals:** `app/Services/ApprovalService.php` — submit, assign
  reviewers, **sequential stages**, approve / reject /
  **request changes**, comments, full step history, notifications at every
  transition, **configurable workflow templates per department/category**.
- **Versions:** new-version upload now **preserves all old versions**
  (destructive plan-cap pruning removed), version number + note +
  uploaded-by + timestamp, restore, metadata comparison UI.
- **Security:** unchanged guarantees kept and extended — randomized stored
  names, MIME validation, plan file-size + storage quotas, private storage
  outside web root, authenticated download controller, tokens never expose
  paths.
- **Subscriptions:** existing plan system retained (the prompt's
  Free/Starter/Business/Enterprise naming maps onto the seeded
  Free/Starter/Professional/Business/Enterprise tiers), monthly/yearly,
  trials, storage/user/team limits, renewals, invoices, coupons,
  upgrade/downgrade, expiry + grace — **payments remain verified-only**
  through `PaymentService::confirmPayment()`.
- **Reports:** activity, storage, user activity, sharing, approvals, team
  performance — CSV exports + printable (print stylesheet) views.
- Notification centre pages, global search (topbar → tree file managers +
  `api/search`), audit logs page.

## 4. Database — migrations (non-destructive)

`database/migrations/` (apply 004 before 003 on existing installs):
001 document_favorites · 002 share passwords + share_access_logs ·
003 approval_workflows (+ steps, `approvals.workflow_id`,
`changes_requested`/`skipped` states) · 004 departments +
documents.department_id/expires_at + document_properties.
Everything is folded into `database/database.sql` (now **41 tables**), so
fresh installs via `database/install.php` need no extra steps. Demo seeds
extended: 4 departments, document departments/expiry, favourites, custom
properties, one password-protected share link, a 2-stage workflow + a
pending approval for the manager. **All demo logins unchanged**
(password `password`): super@docuflow.test, admin@demo.com,
manager@demo.com, member@demo.com, viewer@demo.com; coupon `WELCOME10`.
Seeded share link: `/s/demotoken000000000000000000000000000000000000001`
(password `password`).

## 5. vercel-demo (static showcase, design preserved)

File manager now mirrors the app: checkbox bulk selection (grid + list +
select-all) with bulk bar (favourite/download/delete on mock data), sort
control (name/size/downloads/newest), grid/list toggle **persisted**,
favourite stars, storage meter computed from the mock corpus, toast
notifications, folder navigation + live search retained.

## 6. Bugs found and fixed

**Pre-existing (original build — the PHP app had evidently never been run
end-to-end; all six were runtime-fatal):**

1. `routes/web.php`: `route()` declared `array $handler` but static-page
   routes pass Closures — guaranteed TypeError on boot. Widened to
   `array|Closure`.
2. `database/database.sql`: `invitations` and `invoices` both named a
   foreign key `fk_inv_tenant` (InnoDB requires schema-unique FK names),
   so a fresh import died at `invoices`. Renamed the invitations one to
   `fk_invit_tenant`.
3. `config/app.php`: required twice per request (bootstrap + the lazy
   `config()` helper) while declaring `env()` unguarded — "Cannot
   redeclare env()" fatal on the first `url()`/`config()` call in every
   page. Declaration is now wrapped in `function_exists`.
4. The `Folder` class lived inside `app/Models/Document.php`, but the
   autoloader maps one class to one file — `Folder` failed to load unless
   `Document` had loaded first. Extracted to `app/Models/Folder.php`.
5. `TenantController::docOrFail()` (and `deepVersion` path) called
   `Document::one()`, which does not exist. Now `Database::one()`.
6. `SubscriptionService::activate()`: INSERT listed 8 columns with only
   7 values and `"active"` in the `billing_cycle` position — verified
   payments cancelled the old plan but never created the new one. Fixed
   placeholder/column alignment.

**Introduced by this upgrade, caught by the runtime tests below:**

7. `ApiController::upload()` INSERT had 11 placeholders for 12 params.
8. `Database::insert()` called with a table name instead of SQL in
   `Document::toggleFavorite()` and `ApprovalService::request()` (3
   sites) — rewritten as raw INSERTs.
9. `ApprovalService::decide()` hardening: a company admin could record a
   decision on another approver's current step via a crafted POST.
   Decisions are now assignee-only; stages still run strictly in order.

## 7. Test results

- **PHP lint: 123/123 files pass** (`php -l`, PHP 8.3.13 static binary).
- **Route smoke test:** bootstrapped the app in CLI, loaded
  `routes/web.php` — 164 routes registered; all 31 new tree/API routes
  present; **every handler resolves to a real controller method**; all 36
  new view files exist on disk.
- **Full runtime verification (MariaDB 10.11 + PHP built-in server,
  2026-11-06):** a local MariaDB was installed after all, so the flows
  previously listed as untestable were exercised end-to-end over HTTP:
  - `database/install.php` imports cleanly: **41 tables**; all demo
    users/seeds present (documents 3, departments 4, favorites 3,
    properties 4, workflow steps 2).
  - Logins 302 to the correct tree per role; **all 30 tree pages** render
    with real data; classic `/app`, `/super` pages render; role
    isolation verified (member→admin tree redirects to own tree,
    member→super-admin = 403).
  - API permission filtering: member sees only accessible documents,
    viewer sees none, admin sees all; `api/stats`/`api/charts` return
    real aggregates.
  - Upload → authenticated download round-trip returns the exact file;
    bulk trash → trash listing → bulk restore; favourite toggle;
    rename; password-protected share creation.
  - Approvals: seeded 2-stage workflow progresses stage-by-stage
    (manager then admin) to `approved`, requester notified;
    `changes_requested` finalises and marks the remaining step
    `skipped`; out-of-turn decisions rejected.
  - Public share: gate shown, download blocked pre-unlock, wrong
    password rejected + logged, correct password (`password`) unlocks,
    file downloads; `share_access_logs` recorded view/download/
    unlock_failed.
  - Versions: v2 upload preserves v1 (with note), restore v1 works.
  - Payments: plan change to Business + WELCOME10 → pending payment of
    $71.10, plan unchanged; super-admin verify → payment + invoice
    `paid`, Business subscription `active`, old plan cancelled.
  - Migrations: simulated pre-upgrade DB (35 tables) → applying
    001, 002, 004, 003 → **41 tables**, all new columns/enums/FKs
    present and correct.
- **JS:** `node --check` passes for `docuflow.js` and demo `demo.js`.
- Test artifacts (local `.env`, scratch databases, uploaded test files,
  test server) were removed after verification; only the three seed
  files remain in `storage/private/`.

## 8. Known limitations

- Bulk “Download” opens each file's authenticated download URL in turn
  (browser pop-up settings may require allowing multiple windows); there is
  no server-side ZIP bundling.
- Share-link “views” still increments per unlocked view; the access log is
  the precise audit trail.
- Workflow template steps resolve approvers by explicit user (role-based
  step resolution exists in `ApprovalService` for API-created templates).
- The public REST contract in `docs/API.md` (`/api/v1/*`, bearer tokens,
  webhooks) remains an architecture contract; the **implemented** API is
  the session-based internal one documented alongside it.
- Core PHP/MySQL cannot run on Vercel: the PHP app still needs a
  PHP/MySQL host; `vercel-demo/` remains the static Vercel showcase.

## 9. Remaining tasks for the parent (out of scope here)

- Commit + push to https://github.com/AiwithFahad/docuflow-cloud-saas
  (no git/GitHub/Vercel commands were run, per instructions).
- Redeploy the `vercel-demo/` static showcase to Vercel.
- On a PHP/MySQL host: import updated `database.sql` (or apply the
  migrations to an existing DB in dependency order 001 → 002 → 004 →
  003), then run the README smoke flow per role. Both paths were
  verified locally against MariaDB 10.11 (see §7).
