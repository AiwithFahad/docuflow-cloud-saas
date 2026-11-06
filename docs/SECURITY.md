# DocuFlow Cloud — Security Documentation

## Tenant isolation
- Every business table (`folders`, `documents`, `teams`, `categories`, `tags`,
  `document_versions`, `document_permissions`, `document_shares`, `approvals`,
  `comments`, …) carries `tenant_id` with a foreign key to `tenants`.
- All model/controller queries are tenant-scoped. Document access additionally
  passes through `Document::userCanAccess()`, which verifies the document's
  tenant, the user's role and any explicit user/team permission rows.
- Super Admin accounts are the only users with `tenant_id = NULL`.

## Authentication
- Passwords hashed with `password_hash()` (bcrypt) and verified with
  `password_verify()`; demo seed hash is the bcrypt of `password` — **change
  all demo passwords before any real deployment**.
- Login rate limiting: 5 failed attempts per email per 15 minutes
  (`login_attempts` table).
- Sessions: HttpOnly + SameSite=Lax cookies, `secure` when HTTPS, session ID
  regenerated at login, 2-hour lifetime.
- Password reset tokens are stored SHA-256 hashed, expire in 1 hour, and are
  deleted after use. Email verification tokens are single-use.
- Optional 2FA: the schema and RBAC allow bolting a TOTP step onto
  `AuthController::login()`; not enabled by default (see roadmap in README).

## Request integrity
- CSRF token (session-bound, `hash_equals` comparison) required on every
  state-changing POST; AJAX can send `X-CSRF-Token`.
- PDO prepared statements with emulated prepares disabled everywhere.
- All output escaped via `e()` (htmlspecialchars, ENT_QUOTES, UTF-8).
- Server-side validation on every form (email format, lengths, enums).

## File security
- Uploads validated three ways: extension allow-list, real MIME sniffing via
  `finfo`, and per-plan size limits (plus a hard server ceiling).
- Executable types (php, exe, sh, js, html, svg, …) are hard-blocked.
- Files are stored under `storage/private/` — **outside the `public/` web
  root** — with 160-bit randomized filenames (`FileSecurity::storedName()`).
- Downloads/previews are streamed only by controllers after authorization;
  `FileSecurity::resolvePath()` rejects any path that escapes the storage
  directory (traversal-proof), and downloads send `nosniff` headers.
- Public share links use 192-bit random tokens, support expiry + download
  toggles + instant revocation, and never expose document IDs' storage names.

## Billing integrity
- Payments start as `pending`. `PaymentService::confirmPayment()` is the only
  path to `paid`, and it requires either a gateway `verify()` success or an
  explicit Super Admin manual verification. Unverified payments are clearly
  labelled everywhere in the UI.

## Auditability
- `activity_logs` (who did what, with IP) and `audit_logs` (entity-level
  before/after JSON for documents, users, settings) are written on sensitive
  operations and viewable in both tenant and Super Admin areas.

## Deployment hardening checklist
1. Serve only `public/` as the web root; keep `.env`, `storage/`, `app/` private.
2. Use HTTPS; set `APP_DEBUG=false`; use a strong, unique `DB_PASS`.
3. Restrict `storage/` permissions (0770) to the web server user.
4. Back up MySQL + `storage/private/` together (see `storage/backups/`).
5. Remove or re-password all demo accounts.
