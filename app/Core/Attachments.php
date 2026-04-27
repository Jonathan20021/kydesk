<?php
namespace App\Core;

/**
 * Helper centralizado para attachments de tickets.
 *  - Validación de mime + tamaño
 *  - Almacenamiento en /public/uploads/tickets/{tenant_id}/{yyyymm}/
 *  - Filename randomizado (no se filtra el nombre original al filesystem)
 *  - Inserta row en ticket_attachments
 */
class Attachments
{
    /** Tipos permitidos: imágenes, docs, archivos comunes. */
    public const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'image/heic' => 'heic', 'image/heif' => 'heif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/zip' => 'zip',
        'application/x-rar-compressed' => 'rar', 'application/vnd.rar' => 'rar',
        'application/x-7z-compressed' => '7z',
        'text/plain' => 'txt',
        'text/csv' => 'csv', 'application/csv' => 'csv',
        'application/json' => 'json',
        'video/mp4' => 'mp4', 'video/quicktime' => 'mov',
        'audio/mpeg' => 'mp3', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/webm' => 'webm',
        'application/octet-stream' => 'bin',
    ];

    /** 25 MB por archivo. */
    public const MAX_BYTES = 26214400;
    public const MAX_FILES = 10;

    /**
     * Procesa todos los archivos subidos en $_FILES[$inputName] (multi-file)
     * y los persiste vinculados al ticket / comment opcional.
     *
     * Devuelve un array de ids de attachments creados.
     */
    public static function persistFromInput(string $inputName, int $tenantId, int $ticketId, ?int $commentId = null): array
    {
        if (empty($_FILES[$inputName])) return [];
        $f = $_FILES[$inputName];

        // Normalizar para multi-upload (PHP entrega arrays separados por columna)
        $files = [];
        if (is_array($f['name'] ?? null)) {
            $count = count($f['name']);
            for ($i = 0; $i < $count; $i++) {
                if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $files[] = [
                    'name'     => (string)$f['name'][$i],
                    'tmp_name' => (string)$f['tmp_name'][$i],
                    'size'     => (int)$f['size'][$i],
                    'type'     => (string)($f['type'][$i] ?? ''),
                    'error'    => (int)$f['error'][$i],
                ];
            }
        } else {
            if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $files[] = $f;
        }

        if (empty($files)) return [];
        $files = array_slice($files, 0, self::MAX_FILES);

        $app = Application::get();
        $db = $app->db;
        $ids = [];

        $relBase = '/public/uploads/tickets/' . $tenantId . '/' . date('Ym');
        $absBase = BASE_PATH . $relBase;
        if (!is_dir($absBase)) @mkdir($absBase, 0755, true);

        foreach ($files as $u) {
            if (($u['error'] ?? 0) !== UPLOAD_ERR_OK) continue;
            if ((int)$u['size'] <= 0 || (int)$u['size'] > self::MAX_BYTES) continue;

            $mime = self::detectMime($u['tmp_name'], $u['type'] ?? '');
            if (!isset(self::ALLOWED_MIMES[$mime])) {
                // Fallback by extension when mime is generic
                $extGuess = strtolower(pathinfo($u['name'], PATHINFO_EXTENSION));
                $mimeByExt = array_search($extGuess, self::ALLOWED_MIMES, true);
                if ($mimeByExt === false) continue;
                $mime = $mimeByExt;
            }
            $ext = self::ALLOWED_MIMES[$mime];

            $stored = 'tk_' . $ticketId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $absPath = $absBase . '/' . $stored;
            if (!@move_uploaded_file($u['tmp_name'], $absPath)) continue;

            $original = self::sanitizeOriginalName((string)$u['name']);
            try {
                $id = $db->insert('ticket_attachments', [
                    'tenant_id'     => $tenantId,
                    'ticket_id'     => $ticketId,
                    'comment_id'    => $commentId,
                    'filename'      => $relBase . '/' . $stored,
                    'original_name' => $original,
                    'mime'          => $mime,
                    'size'          => (int)$u['size'],
                ]);
                $ids[] = (int)$id;
            } catch (\Throwable $e) {
                @unlink($absPath);
            }
        }
        return $ids;
    }

    public static function detectMime(string $tmpPath, string $fallback = ''): string
    {
        if (function_exists('mime_content_type') && is_file($tmpPath)) {
            $m = @mime_content_type($tmpPath);
            if ($m) return $m;
        }
        if (function_exists('finfo_open')) {
            $f = @finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $m = @finfo_file($f, $tmpPath);
                @finfo_close($f);
                if ($m) return $m;
            }
        }
        return $fallback ?: 'application/octet-stream';
    }

    public static function sanitizeOriginalName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? $name;
        $name = trim($name);
        if ($name === '') $name = 'archivo';
        return mb_substr($name, 0, 255);
    }

    /**
     * Devuelve la URL pública para un attachment (usando relative path stored).
     */
    public static function publicUrl(string $relPath): string
    {
        $app = Application::get();
        $base = rtrim($app->config['app']['url'] ?? '', '/');
        // El relPath empieza con /public/...; servimos /uploads/... directamente
        $relPath = str_replace('/public/', '/', $relPath);
        return $base . $relPath;
    }

    /** Útil para iconos en el UI. */
    public static function iconFor(string $mime): string
    {
        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'music';
        if ($mime === 'application/pdf') return 'file-text';
        if (in_array($mime, ['application/zip','application/x-rar-compressed','application/vnd.rar','application/x-7z-compressed'], true)) return 'archive';
        if (in_array($mime, ['application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true)) return 'file-text';
        if (in_array($mime, ['application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','text/csv','application/csv'], true)) return 'sheet';
        if (in_array($mime, ['application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation'], true)) return 'presentation';
        return 'file';
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' MB';
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }
}
