<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One-time migration command to encrypt existing plain-text PII fields.
 *
 * Run ONCE after deploying the encrypted cast changes to Tenant and Landlord models.
 * This command reads raw values directly via DB:: (bypassing model casts) and
 * re-writes them as Laravel encrypted ciphertext.
 *
 * Usage:
 *   php artisan pii:encrypt --dry-run   # Preview without making changes
 *   php artisan pii:encrypt             # Encrypt all existing plain-text PII
 *
 * IMPORTANT: Take a database backup before running this command.
 */
class EncryptPiiCommand extends Command
{
    protected $signature = 'pii:encrypt
                            {--dry-run : Preview changes without writing to the database}
                            {--force : Skip confirmation prompt}';

    protected $description = 'One-time migration: encrypt existing plain-text PII fields (national_id, passport_number, pin_number)';

    /**
     * Tables and fields to encrypt.
     * All fields are encrypted using Laravel's Crypt::encryptString().
     */
    private const TARGETS = [
        'tenants'   => ['national_id', 'passport_number', 'pin_number'],
        'landlords' => ['national_id', 'passport_number', 'pin_number'],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('');
        $this->info('━━━ PII Encryption Migration ━━━');
        $this->info('');

        if ($dryRun) {
            $this->warn('  [DRY RUN] No database changes will be made.');
        }

        if (! $dryRun && ! $this->option('force')) {
            $this->warn('  ⚠️  This will ENCRYPT all plain-text national_id, passport_number,');
            $this->warn('     and pin_number values in the tenants and landlords tables.');
            $this->warn('  ⚠️  Ensure you have a fresh database BACKUP before proceeding.');
            $this->info('');

            if (! $this->confirm('Have you taken a backup and are ready to proceed?')) {
                $this->info('Aborted. No changes made.');
                return self::SUCCESS;
            }
        }

        $totalEncrypted = 0;
        $totalSkipped   = 0;
        $totalErrors    = 0;

        foreach (self::TARGETS as $table => $fields) {
            $this->info("  Processing table: {$table}");
            [$encrypted, $skipped, $errors] = $this->encryptTable($table, $fields, $dryRun);
            $totalEncrypted += $encrypted;
            $totalSkipped   += $skipped;
            $totalErrors    += $errors;
        }

        $this->info('');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows encrypted', $totalEncrypted],
                ['Rows skipped (already encrypted or null)', $totalSkipped],
                ['Rows with errors', $totalErrors],
            ],
        );

        if ($totalErrors > 0) {
            $this->error("  {$totalErrors} row(s) had errors. Check logs for details.");
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('  Dry run complete. Run without --dry-run to apply changes.');
        } else {
            $this->info('  ✓ PII encryption complete.');
            Log::info('PII encryption migration completed', [
                'encrypted' => $totalEncrypted,
                'skipped'   => $totalSkipped,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Encrypt PII fields for a single table.
     *
     * Returns [encrypted_count, skipped_count, error_count].
     */
    private function encryptTable(string $table, array $fields, bool $dryRun): array
    {
        $encrypted = 0;
        $skipped   = 0;
        $errors    = 0;

        // Read raw database values — bypass model casts entirely
        DB::table($table)->orderBy('id')->chunk(200, function ($rows) use (
            $table, $fields, $dryRun, &$encrypted, &$skipped, &$errors
        ) {
            foreach ($rows as $row) {
                $updates = [];

                foreach ($fields as $field) {
                    $rawValue = $row->$field ?? null;

                    if ($rawValue === null || $rawValue === '') {
                        $skipped++;
                        continue;
                    }

                    // Detect already-encrypted values: Laravel ciphertext is base64-encoded
                    // JSON and always starts with "eyJ" (base64 of '{"iv"').
                    if (str_starts_with($rawValue, 'eyJ')) {
                        $skipped++;
                        continue;
                    }

                    $updates[$field] = Crypt::encryptString($rawValue);
                }

                if (empty($updates)) {
                    continue;
                }

                if (! $dryRun) {
                    try {
                        DB::table($table)->where('id', $row->id)->update($updates);
                        $encrypted += count($updates);
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::error("PII encryption failed for {$table} row {$row->id}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    // Dry run: just count what would be encrypted
                    $encrypted += count($updates);
                }
            }
        });

        $this->line("    {$table}: {$encrypted} values encrypted, {$skipped} skipped, {$errors} errors");

        return [$encrypted, $skipped, $errors];
    }
}
