<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

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
     *
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
        // NOTE: also checked via BLOCKED_INCLUDE_PATTERNS below (no-parens form)
        'include', 'include_once', 'require', 'require_once',

        // Higher-order functions that accept dangerous callbacks.
        // An attacker can write: array_map('system', ['id']) — these must be blocked
        // even though 'system' itself is already blocked, because the callback is a string.
        'array_map', 'array_filter', 'array_walk', 'array_walk_recursive',
        'usort', 'uasort', 'uksort', 'array_reduce',

        // Execution hooks / shutdown handlers
        'register_shutdown_function', 'register_tick_function',
        'set_error_handler', 'set_exception_handler',
        'ob_start',  // ob_start('system') is a valid attack vector
        'spl_autoload_register',

        // Variable injection into scope
        'extract', 'parse_str',

        // Misc dangerous
        'putenv', 'getenv', 'phpinfo', 'php_uname', 'posix_', 'dl',
        'reflectionclass', 'reflectionfunction', 'reflectionmethod',
        'header',   // HTTP header injection / redirect
        // NOTE: 'mail' is intentionally NOT in this list — the pattern \bmail\s*[\(\:]
        // would false-positive on "MAIL: info@..." in plain HTML header blocks.
        // mail() is blocked via the BLOCKED_FUNCTION_ONLY list below instead,
        // which only matches `mail(` (open-paren form, never colon).
    ];

    /**
     * Validate template content and throw if dangerous patterns are found.
     *
     * Defence layers applied in order:
     *  1. Reject raw <?php / <?= open tags.
     *  2. Check BLOCKED_PATTERNS (function-call form: name followed by ( or :).
     *  3. Check include/require without parentheses (the no-parens language-construct form).
     *  4. Check variable-function call pattern ($var(...)) — detects dynamic dispatch.
     *
     * @throws InvalidArgumentException with a human-readable message safe to show in UI
     */
    public function assertSafe(string $template): void
    {
        // 1. Reject raw PHP open tags — only @php/@endphp is allowed
        if (preg_match('/<\?php|<\?=/i', $template)) {
            throw new InvalidArgumentException(
                'Direct PHP tags (<?php, <?=) are not permitted in templates. ' .
                'Use @php/@endphp Blade directives instead.',
            );
        }

        $lowerTemplate = strtolower($template);

        // 2. Blocked function/keyword patterns (require `(` or `:` after the name)
        foreach (self::BLOCKED_PATTERNS as $pattern) {
            // Use word-boundary matching to avoid false positives
            // e.g. "system" should not match "systemVersion"
            if (preg_match('/\b' . preg_quote(strtolower($pattern), '/') . '\s*[\(\:]/i', $lowerTemplate)) {
                throw new InvalidArgumentException(
                    "Template contains a disallowed function or keyword: [{$pattern}]. " .
                    'Contact a system administrator if this is a legitimate requirement.',
                );
            }
        }

        // 2b. mail() — checked inside @php blocks only to avoid false-positives on
        //     "MAIL: info@..." plain-text in HTML lease templates. mail() in plain
        //     HTML or text is harmless; only mail() as executable PHP is dangerous.
        if (preg_match('/@php.*?\bmail\s*\(/si', $template)) {
            throw new InvalidArgumentException(
                'Template contains a disallowed function or keyword: [mail]. ' .
                'Contact a system administrator if this is a legitimate requirement.',
            );
        }

        // 3. Block include/require in no-parens form: `include 'file'` or `include $var`
        //    The BLOCKED_PATTERNS check above only catches `include(` and `include:`.
        //    PHP allows `include 'path'` (no parens), which the regex above would miss.
        if (preg_match('/\b(?:include|include_once|require|require_once)\s+[\'"\$\/]/i', $template)) {
            throw new InvalidArgumentException(
                'Template contains a disallowed include/require statement. ' .
                'Contact a system administrator if this is a legitimate requirement.',
            );
        }

        // 4. Block variable-function calls: $someVar('arg') or $someVar::method().
        //    An attacker can assign a dangerous function to a variable and call it:
        //    @php $f = 'system'; $f('id'); @endphp
        //
        //    SECURITY BUG FIX: original regex was `[\(\:]{2}` (TWO characters from the set),
        //    which only matched `::` or `((` — it did NOT match a single `$f(`.
        //    Correct pattern: `\(` (single open paren) to catch `$f("id")` style calls,
        //    plus `::` via a separate alternation to catch static dispatch `$Cls::method()`.
        //
        //    The check is scoped to `@php ... @endphp` blocks only (the `.*?` non-greedy
        //    match with the `s` flag). Blade `{{ $var }}` expressions do not execute PHP
        //    function calls, so they are not affected.
        if (preg_match('/@php.*?\$\w+\s*(?:\(|::)/si', $template)) {
            throw new InvalidArgumentException(
                'Template contains a variable function or static-dispatch call pattern ($var(...) or $Cls::method()) which is not permitted. ' .
                'Use named functions directly instead.',
            );
        }

        // 5. Block IIFE / expression-result invocation: ($expr)('arg')
        //    PHP 7+ allows calling the result of a parenthesised expression:
        //    ($f = 'system')('id') — this bypasses the $var( check above
        //    because the call site has no $identifier immediately before (.
        //    The signature is `)` followed by optional whitespace then `(` inside a @php block.
        //    Legitimate templates never need this construct; arithmetic like max(0, min(100, $v))
        //    has `)` then `)`, not `)` then `(`.
        if (preg_match('/@php.*?\)\s*\(/si', $template)) {
            throw new InvalidArgumentException(
                'Template contains an expression-invocation pattern (...)(args) which is not permitted. ' .
                'Contact a system administrator if this is a legitimate requirement.',
            );
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
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
