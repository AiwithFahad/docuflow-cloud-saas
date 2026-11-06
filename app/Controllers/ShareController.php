<?php
// DocuFlow Cloud — public share links. Access is granted ONLY by a valid,
// active, unexpired random token. Nothing else about the document leaks.
// Links may additionally be password-protected (bcrypt); every view,
// download and failed unlock attempt is written to share_access_logs.
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

    private function log(array $share, string $action): void
    {
        Database::run('INSERT INTO share_access_logs (tenant_id, share_id, action, ip_address, user_agent, created_at)
                       VALUES (?,?,?,?,?,NOW())',
            [(int) $share['tenant_id'], (int) $share['id'], $action,
             $_SERVER['REMOTE_ADDR'] ?? null, substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
        Database::run('UPDATE document_shares SET last_accessed_at = NOW() WHERE id = ?', [(int) $share['id']]);
    }

    private function unlocked(array $share): bool
    {
        if (empty($share['password_hash'])) return true;
        return !empty($_SESSION['share_ok_' . (int) $share['id']]);
    }

    public function show(string $token): void
    {
        $share = $this->shareOrFail($token);
        if (!$this->unlocked($share)) {
            view('website/share-password', ['share' => $share, 'token' => $token, 'error' => null]);
            return;
        }
        Database::run('UPDATE document_shares SET views = views + 1 WHERE id = ?', [(int) $share['id']]);
        $this->log($share, 'view');
        view('website/share', ['share' => $share, 'token' => $token]);
    }

    public function unlock(string $token): void
    {
        require_csrf();
        $share = $this->shareOrFail($token);
        $key = 'share_fail_' . (int) $share['id'];
        if (($_SESSION[$key] ?? 0) >= 5) {
            http_response_code(429);
            view('website/share-password', ['share' => $share, 'token' => $token,
                'error' => 'Too many failed attempts. Please wait and try again later.']);
            return;
        }
        $password = (string) ($_POST['password'] ?? '');
        if (!empty($share['password_hash']) && password_verify($password, (string) $share['password_hash'])) {
            $_SESSION['share_ok_' . (int) $share['id']] = true;
            unset($_SESSION[$key]);
            redirect('s/' . $token);
        }
        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
        $this->log($share, 'unlock_failed');
        view('website/share-password', ['share' => $share, 'token' => $token, 'error' => 'Incorrect password.']);
    }

    public function download(string $token): void
    {
        $share = $this->shareOrFail($token);
        if (!$this->unlocked($share)) {
            redirect('s/' . $token);
        }
        if (empty($share['can_download'])) {
            http_response_code(403);
            exit('Downloads are disabled for this share link.');
        }
        $path = FileSecurity::resolvePath((string) $share['stored_name']);
        if (!$path) { http_response_code(404); exit('File missing.'); }
        Database::run('UPDATE documents SET download_count = download_count + 1 WHERE id = ?', [(int) $share['document_id']]);
        $this->log($share, 'download');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . rawurlencode((string) $share['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
