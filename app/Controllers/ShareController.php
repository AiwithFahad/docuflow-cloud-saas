<?php
// DocuFlow Cloud — public share links. Access is granted ONLY by a valid,
// active, unexpired random token. Nothing else about the document leaks.
declare(strict_types=1);

final class ShareController
{
    private function shareOrFail(string $token): array
    {
        $share = Database::one(
            'SELECT s.*, d.name, d.original_name, d.extension, d.mime_type, d.size_bytes, d.stored_name,
                    t.name AS company, t.status AS tenant_status
             FROM document_shares s
             JOIN documents d ON d.id = s.document_id
             JOIN tenants t ON t.id = s.tenant_id
             WHERE s.token = ? AND s.is_active = 1 AND d.deleted_at IS NULL
               AND (s.expires_at IS NULL OR s.expires_at > NOW())',
            [$token]
        );
        if (!$share || $share['tenant_status'] === 'suspended') {
            http_response_code(404);
            view('errors/share-expired');
            exit;
        }
        return $share;
    }

    public function show(string $token): void
    {
        $share = $this->shareOrFail($token);
        Database::run('UPDATE document_shares SET views = views + 1 WHERE id = ?', [(int) $share['id']]);
        view('website/share', ['share' => $share, 'token' => $token]);
    }

    public function download(string $token): void
    {
        $share = $this->shareOrFail($token);
        if (empty($share['can_download'])) {
            http_response_code(403);
            exit('Downloads are disabled for this share link.');
        }
        $path = FileSecurity::resolvePath((string) $share['stored_name']);
        if (!$path) { http_response_code(404); exit('File missing.'); }
        Database::run('UPDATE documents SET download_count = download_count + 1 WHERE id = ?', [(int) $share['document_id']]);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . rawurlencode((string) $share['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
