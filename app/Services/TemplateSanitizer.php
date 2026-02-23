<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Validates Blade template content submitted by admin users before storage.
 *
 * Lease templates are stored in the database and rendered server-side via
 * Blade::render(). A malicious or compromised admin could inject PHP functions
 * (system(), exec(), etc.) inside @php blocks or Blade expressions to achieve
 * Remote Code Execution. This service enforces an allowlist approach at both
 * form submission time AND at render time (defense in depth).
 */
class TemplateSanitizer
{
    /**
     * PHP functions and language constructs that are NEVER allowed inside
     * @php blocks or Blade expressions in lease templates.
     */
    private const BLOCKED_PATTERNS = [
        // OS command execution
        'system', 'exec', 'shell_exec', 'passthru', 'popen', 'proc_open',
        'proc_close', 'proc_get_status', 'proc_terminate',

        // Code execution
        'eval', 'assert', 'call_user_func', 'call_user_func_array',
        'create_function', 'preg_replace_callback',

        // File system
        'file_get_contents', 'file_put_contents', 'file_exists', 'file',
        'fopen', 'fwrite', 'fread', 'fclose', 'unlink', 'rename', 'copy',
        'mkdir', 'rmdir', 'scandir', 'glob', 'chmod', 'chown', 'chgrp',

        // Network
        'curl_init', 'curl_exec', 'fsockopen', 'pfsockopen', 'stream_socket_client',
        'socket_create', 'socket_connect', 'http_get', 'get_headers',

        // Serialization (object injection)
        'unserialize', 'yaml_parse', 'simplexml_load_string', 'simplexml_load_file',

        // Obfuscation helpers
        'base64_decode', 'str_rot13', 'gzinflate', 'gzuncompress', 'gzdecode',
        'hex2bin', 'convert_uuencode', 'convert_uudecode',

        // Include/require (allows loading arbitrary PHP files)
        'include', 'include_once', 'require', 'require_once',

        // Misc dangerous
        'putenv', 'getenv', 'phpinfo', 'php_uname', 'posix_', 'dl',
        'reflectionclass', 'reflectionfunction', 'reflectionmethod',
    ];

    /**
     * Validate template content and throw if dangerous patterns are found.
     *
     * @throws \InvalidArgumentException with a human-readable message safe to show in UI
     */
    public function assertSafe(string $template): void
    {
        // Reject raw PHP open tags — only @php/@endphp is allowed
        if (preg_match('/<\?php|<\?=/i', $template)) {
            throw new \InvalidArgumentException(
                'Direct PHP tags (<?php, <?=) are not permitted in templates. ' .
                'Use @php/@endphp Blade directives instead.'
            );
        }

        $lowerTemplate = strtolower($template);

        foreach (self::BLOCKED_PATTERNS as $pattern) {
            // Use word-boundary matching to avoid false positives
            // e.g. "system" should not match "systemVersion"
            if (preg_match('/\b' . preg_quote(strtolower($pattern), '/') . '\s*[\(\:]/i', $lowerTemplate)) {
                throw new \InvalidArgumentException(
                    "Template contains a disallowed function or keyword: [{$pattern}]. " .
                    'Contact a system administrator if this is a legitimate requirement.'
                );
            }
        }
    }

    /**
     * Check if template content is safe (non-throwing version).
     */
    public function isSafe(string $template): bool
    {
        try {
            $this->assertSafe($template);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
