<?php

namespace App\Console\Commands;

use App\Models\Lease;
use App\Services\QRCodeService;
use App\Services\SerialNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateLeaseQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leases:generate-qr-codes
                            {--all : Generate for all leases, even those with existing QR codes}
                            {--missing-only : Only generate for leases without QR codes (default)}
                            {--serial-only : Only generate serial numbers}
                            {--qr-only : Only generate QR codes (requires serial numbers)}
                            {--batch=50 : Number of leases to process in each batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate QR codes and serial numbers for existing leases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting QR code and serial number generation...');
        $this->newLine();

        // Determine which leases to process
        $query = Lease::query();

        if ($this->option('all')) {
            $this->warn('⚠️  Processing ALL leases (will regenerate existing QR codes)');
        } else {
            if (!$this->option('qr-only')) {
                $query->whereNull('serial_number');
            }
            if (!$this->option('serial-only')) {
                $query->orWhereNull('qr_code_data');
            }
            $this->info('ℹ️  Processing only leases with missing data');
        }

        $totalLeases = $query->count();

        if ($totalLeases === 0) {
            $this->info('✅ No leases need processing. All done!');
            return self::SUCCESS;
        }

        $this->info("📊 Found {$totalLeases} lease(s) to process");
        $this->newLine();

        if (!$this->confirm('Do you want to continue?', true)) {
            $this->warn('Operation cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();
        $batchSize = (int) $this->option('batch');
        $progressBar = $this->output->createProgressBar($totalLeases);
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Process in batches to avoid memory issues
        $query->chunk($batchSize, function ($leases) use (
            &$successCount,
            &$errorCount,
            &$skippedCount,
            &$errors,
            $progressBar
        ) {
            foreach ($leases as $lease) {
                try {
                    DB::beginTransaction();

                    $updated = false;

                    // Generate serial number if needed
                    if (!$this->option('qr-only') && empty($lease->serial_number)) {
                        try {
                            $prefix = config('lease.serial_number.prefix', 'LSE');
                            $lease->serial_number = SerialNumberService::generateUnique($prefix);
                            $lease->saveQuietly();
                            $updated = true;
                        } catch (\Exception $e) {
                            throw new \Exception("Failed to generate serial number: {$e->getMessage()}");
                        }
                    }

                    // Generate QR code if needed
                    if (!$this->option('serial-only')) {
                        if (empty($lease->serial_number)) {
                            $skippedCount++;
                            $errors[] = "Lease #{$lease->id} ({$lease->reference_number}): No serial number available";
                            $progressBar->advance();
                            DB::rollBack();
                            continue;
                        }

                        if ($this->option('all') || empty($lease->qr_code_data)) {
                            try {
                                QRCodeService::attachToLease($lease);
                                $updated = true;
                            } catch (\Exception $e) {
                                throw new \Exception("Failed to generate QR code: {$e->getMessage()}");
                            }
                        }
                    }

                    if ($updated) {
                        $successCount++;
                    } else {
                        $skippedCount++;
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errorCount++;
                    $errors[] = "Lease #{$lease->id} ({$lease->reference_number}): {$e->getMessage()}";
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('📈 Processing Complete!');
        $this->newLine();

        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Success', $successCount],
                ['⏭️  Skipped', $skippedCount],
                ['❌ Errors', $errorCount],
                ['📊 Total', $totalLeases],
            ]
        );

        // Display errors if any
        if ($errorCount > 0) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
            $this->newLine();
        }

        // Summary message
        if ($errorCount > 0) {
            $this->warn("⚠️  Completed with {$errorCount} error(s). Please review the errors above.");
            return self::FAILURE;
        }

        $this->info('✅ All leases processed successfully!');
        return self::SUCCESS;
    }
}
