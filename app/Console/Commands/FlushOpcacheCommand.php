<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Flush PHP-FPM's opcode cache after a deploy.
 *
 * Because opcache.validate_timestamps=0 is set on the server, PHP-FPM never
 * checks whether source files have changed on disk.  Simply running
 * `git pull` does NOT cause FPM workers to pick up the new bytecode.
 *
 * CLI-side `opcache_reset()` only affects the CLI process (which has its own
 * isolated opcache context).  To reset FPM's cache we must call
 * `opcache_reset()` from *inside* a FPM-handled request.
 *
 * This command does exactly that:
 *   1. Writes a tiny, one-time PHP script to public/.
 *   2. Makes an HTTP GET request to that script via curl (so FPM handles it).
 *   3. Deletes the script immediately.
 *
 * Usage:  php artisan opcache:flush
 * Add to deploy:  php artisan opcache:flush || true
 */
class FlushOpcacheCommand extends Command
{
    protected $signature = 'opcache:flush';

    protected $description = 'Flush PHP-FPM opcode cache by triggering opcache_reset() via an internal HTTP request';

    public function handle(): int
    {
        $token    = bin2hex(random_bytes(16));
        $filename = "opcache_flush_{$token}.php";
        $fullPath = public_path($filename);

        // Write the flush script
        file_put_contents($fullPath, '<?php if(function_exists("opcache_reset")){opcache_reset();echo"OK";}else{echo"SKIP";}');

        try {
            $baseUrl = rtrim(config('app.url'), '/');
            $url     = "{$baseUrl}/{$filename}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // allow self-signed certs on local
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->warn("curl error: {$error}");
                $this->warn('Opcache not flushed — restart PHP-FPM manually if needed.');

                return self::FAILURE;
            }

            if ($status === 200 && str_contains((string) $response, 'OK')) {
                $this->info('PHP-FPM opcode cache flushed successfully.');

                return self::SUCCESS;
            }

            if ($status === 200 && str_contains((string) $response, 'SKIP')) {
                $this->info('opcache not active in FPM — nothing to flush.');

                return self::SUCCESS;
            }

            $this->warn("Unexpected response (HTTP {$status}): {$response}");

            return self::FAILURE;
        } finally {
            // Always delete the temp script, even on error
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
}
