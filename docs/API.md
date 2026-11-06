# DocuFlow Cloud — API & Webhook Architecture

DocuFlow is a server-rendered Core PHP application. Its integration surface
is designed as a REST API layered on the same controllers/services, plus
outbound webhooks. The endpoints below describe the supported contract; the
web UI uses the identical service layer (`SubscriptionService`,
`PaymentService`, `FileSecurity`), so business rules (tenant isolation,
plan limits, payment verification) apply uniformly.

## Authentication
- Session cookie for browser clients.
- API tokens: create per-tenant tokens in the Super Admin roadmap; send as
  `Authorization: Bearer <token>`. Every request is tenant-scoped by token.

## Implemented internal JSON API (used by the workspace UI)

These endpoints are **live in the codebase** (`app/Controllers/ApiController.php`,
driven by `public/assets/js/docuflow.js`). They are session-authenticated
(super admins are rejected — tenant workspaces only), tenant-scoped, and
every document read/write passes `Document::userCanAccess()`. All POSTs
require the CSRF token (form field `_csrf` or `X-CSRF-Token` header).
Responses are JSON: `{ "ok": true, ... }` or `{ "ok": false, "error": "…" }`
with 401/403/404/419/422 status codes as appropriate.

| Method | Path | Description |
|---|---|---|
| GET | `/api/stats` | Real aggregates: documents, folders, storage used/cap/% , active users, shared documents, my pending approvals + requests, favourites, trash, unread notifications, plan name. |
| GET | `/api/charts?type=…` | Chart.js series. Types: `uploads`, `storage` (cumulative MB), `activity`, `categories`, `teams`, `approvals`. Filters: `from`, `to`, `user`, `category`. |
| GET | `/api/documents` | Permission-filtered listing. Params: `q`, `folder` (id/`root`), `category`, `tag`, `department`, `owner`, `status` (`active`/`archived`/`favorites`/`recent`/`any`/`trash`), `sort` (`new`/`old`/`name`/`size`/`downloads`), `from`, `to`, `page`, `per`. |
| GET | `/api/documents/{id}` | Document detail + versions + tags + custom properties. |
| GET | `/api/documents/{id}/versions` | Version list + current metadata (for comparison UI). |
| POST | `/api/documents/bulk` | JSON `{action, ids[], folder_id?}` — actions: `move`, `delete`, `restore`, `purge` (admin), `archive`, `unarchive`, `favorite`, `unfavorite`. |
| POST | `/api/documents/{id}/favorite` | Toggle favourite star. |
| POST | `/api/documents/{id}/rename` | JSON `{name}` rename (edit permission required). |
| POST | `/api/documents/{id}/share` | Create share link `{days, can_download, password?}` → returns the public URL. |
| GET | `/api/folders?folder=` | Child folders with counts + breadcrumb chain + full tree. |
| POST | `/api/folders` | Create folder `{name, parent_id?, color?, description?}`. |
| POST | `/api/upload` | Multipart (`files[]`, optional `folder_id`, `category_id`, `department_id`) — same FileSecurity + quota pipeline as the classic upload. |
| GET | `/api/search?q=` | Global search across accessible documents + folders. |
| GET | `/api/notifications` | Latest 25 notifications + unread count. |
| POST | `/api/notifications/read` | Mark one (`{id}`) or all read. |
| GET | `/api/activity` | Recent activity (tenant-wide for admins, own for others). |

## Endpoints (contract)

| Method | Path | Description |
|---|---|---|
| GET | `/api/v1/documents` | List documents of the caller's tenant. Query: `q`, `category`, `tag`, `sort`, `page`. |
| POST | `/api/v1/documents` | Multipart upload. Same validation + plan-limit pipeline as the UI (`FileSecurity::validate`, storage/user quotas). |
| GET | `/api/v1/documents/{id}` | Metadata + versions + permissions for one document (tenant-checked). |
| GET | `/api/v1/documents/{id}/download` | Permission-checked file stream. |
| POST | `/api/v1/documents/{id}/versions` | Upload a new version; history cap enforced per plan. |
| POST | `/api/v1/documents/{id}/shares` | Create an expiring share token (`days`, `can_download`). |
| DELETE | `/api/v1/documents/{id}/shares/{shareId}` | Revoke a share link. |
| POST | `/api/v1/approvals` | Create an approval request (`document_id`, `approver_id`, `title`). |
| POST | `/api/v1/approvals/steps/{stepId}/decide` | `approved` / `rejected` with optional comment. |
| GET | `/api/v1/reports/usage` | Live usage: storage bytes, users, teams, documents vs plan limits. |

### Example — upload
```bash
curl -X POST https://your-host/api/v1/documents \
  -H "Authorization: Bearer TOKEN" \
  -F "files[]=@contract.pdf" -F "folder_id=4" -F "category_id=1"
```
Response: `201` with the created document metadata (never a storage path).

### Errors
JSON errors with HTTP status: `401` unauthenticated, `403` permission or
tenant mismatch, `404` not found / not in tenant, `413` file too large for
plan, `422` validation failure, `429` login rate limit.

## Webhooks (outbound)
Tenants can subscribe endpoints to events; payloads are HMAC-SHA256 signed
in the `X-DocuFlow-Signature` header.

| Event | Fires when |
|---|---|
| `document.created` | A document finishes uploading |
| `document.version_added` | A new version is stored |
| `document.shared` | A share link is created or revoked |
| `approval.requested` | An approver is asked to decide |
| `approval.decided` | An approval is approved / rejected |
| `subscription.changed` | Plan change, renewal, cancel, expiry |
| `payment.verified` | A payment transitions `pending → paid` (only after verified confirmation) |

Payload shape:
```json
{
  "event": "document.created",
  "tenant_id": 42,
  "occurred_at": "2026-11-06T10:15:00Z",
  "data": { "document_id": 108, "name": "Contract", "size_bytes": 20481 }
}
```

## Rate limits
- Login: 5 failures / 15 min per email (enforced in-app).
- API: 600 requests / minute per tenant token (enforce at the web server).
