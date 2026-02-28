<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Sanitize filenames for Content-Disposition headers to prevent
 * header injection (e.g. newlines, quotes) and path traversal.
 */
class SafeDownloadFilename
{
    private const MAX_LENGTH = 255;

    /**
     * Return a safe filename for use in Content-Disposition.
     * Strips path components, control chars, and keeps only safe characters.
     */
    public static function make(?string $filename, string $fallback = 'document'): string
    {
        if ($filename === null || trim($filename) === '') {
            return $fallback;
        }

        // Remove path traversal and keep basename only
        $name = basename($filename);

        // Remove any character that could break headers (newlines, quotes, etc.)
        $name = preg_replace('/[\r\n"\x00-\x1f\\\\]/', '', $name);
        if ($name === null || $name === '') {
            return $fallback;
        }

        // Keep only alphanumeric, dot, hyphen, underscore, space
        $name = preg_replace('/[^\w\s\.\-]/', '_', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === null || $name === '') {
            return $fallback;
        }

        if (strlen($name) > self::MAX_LENGTH) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $base = pathinfo($name, PATHINFO_FILENAME);
            $name = substr($base, 0, self::MAX_LENGTH - 1 - strlen($ext)) . ($ext ? '.' . $ext : '');
        }

        return $name;
    }
}
