<?php
// DocuFlow Cloud — secure file handling: validation, randomized names,
// private storage outside the web root, traversal-safe path resolution.
declare(strict_types=1);

final class FileSecurity
{
    /** Allowed extension => permitted MIME types. */
    public static array $allowed = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls'  => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ppt'  => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/vnd.ms-excel'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
    ];

    /** Dangerous extensions that are never accepted, even inside archives names. */
    public static array $blocked = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'js', 'html', 'htm', 'svg', 'cgi', 'pl', 'asp', 'aspx', 'jsp'];

    /**
     * Validate an uploaded file (extension allow-list + real MIME sniff +
     * size limit). Returns [ok, errorMessage].
     */
    public static function validate(array $file, int $maxBytes): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [false, 'Upload failed (error ' . (int) ($file['error'] ?? -1) . ').'];
        }
        if ((int) $file['size'] > $maxBytes) {
            return [false, 'File exceeds the maximum allowed size of ' . human_bytes($maxBytes) . '.'];
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($ext === '' || in_array($ext, self::$blocked, true) || !isset(self::$allowed[$ext])) {
            return [false, 'File type ".' . e($ext) . '" is not allowed.'];
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        if (!in_array($mime, self::$allowed[$ext], true)) {
            return [false, 'File content does not match its extension.'];
        }
        return [true, ''];
    }

    /** Generate a collision-free randomized storage name (no user input). */
    public static function storedName(string $originalName): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return bin2hex(random_bytes(20)) . ($ext !== '' ? '.' . $ext : '');
    }

    /**
     * Resolve a stored file path safely inside the private storage dir.
     * Rejects any path traversal attempt.
     */
    public static function resolvePath(string $storedName): ?string
    {
        $base = realpath((string) config('storage_path'));
        if ($base === false) return null;
        $candidate = $base . DIRECTORY_SEPARATOR . basename($storedName);
        $real = realpath($candidate);
        if ($real === false) return null;
        if (!str_starts_with($real, $base . DIRECTORY_SEPARATOR)) return null; // traversal guard
        return $real;
    }

    public static function iconFor(string $ext): string
    {
        return match (strtolower($ext)) {
            'pdf' => 'bi-file-earmark-pdf-fill text-danger',
            'doc', 'docx' => 'bi-file-earmark-word-fill text-primary',
            'xls', 'xlsx', 'csv' => 'bi-file-earmark-excel-fill text-success',
            'ppt', 'pptx' => 'bi-file-earmark-ppt-fill text-warning',
            'png', 'jpg', 'jpeg', 'gif', 'webp' => 'bi-file-earmark-image-fill text-info',
            'zip' => 'bi-file-earmark-zip-fill text-secondary',
            'txt' => 'bi-file-earmark-text-fill text-muted',
            default => 'bi-file-earmark-fill text-secondary',
        };
    }

    public static function isPreviewable(string $ext): bool
    {
        return in_array(strtolower($ext), ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt'], true);
    }
}
