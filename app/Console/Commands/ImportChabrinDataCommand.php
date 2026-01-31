<?php

namespace App\Console\Commands;

use App\Services\ChabrinExcelImportService;
use Illuminate\Console\Command;

class ImportChabrinDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:chabrin-data
                            {--landlords= : Path to landlords Excel file}
                            {--properties= : Path to properties Excel file}
                            {--units= : Path to units Excel file}
                            {--tenants= : Path to tenants Excel file}
                            {--staff= : Path to staff Excel file}
                            {--clean : Truncate existing data before import}
                            {--dry-run : Validate without importing}
                            {--default-paths : Use default paths from storage/app/imports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Chabrin data from Excel files (landlords, properties, units, tenants, staff)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== CHABRIN DATA IMPORT ===');
        $this->newLine();

        // Get file paths
        $filePaths = $this->getFilePaths();

        if (empty($filePaths)) {
            $this->error('No Excel files provided. Use --default-paths or specify individual file paths.');

            return 1;
        }

        // Validate files exist
        foreach ($filePaths as $type => $path) {
            if (! file_exists($path)) {
                $this->error("File not found: {$path}");

                return 1;
            }
            $this->info("✓ Found {$type}: " . basename($path));
        }

        $this->newLine();

        // Confirm clean import
        if ($this->option('clean') && ! $this->option('dry-run')) {
            if (! $this->confirm('This will DELETE all existing landlords, properties, units, tenants, and leases. Continue?', false)) {
                $this->info('Import cancelled.');

                return 0;
            }
        }

        // Show dry-run notice
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No data will be imported');
            $this->newLine();
        }

        // Run import
        $this->info('Starting import...');
        $this->newLine();

        $importService = new ChabrinExcelImportService($this->option('dry-run'));
        $result = $importService->importAll($filePaths, $this->option('clean'));

        // Display results
        $this->displayResults($result);

        return 0;
    }

    /**
     * Get file paths from options or defaults
     */
    protected function getFilePaths(): array
    {
        $paths = [];
        $defaultBasePath = storage_path('app/imports');

        if ($this->option('default-paths')) {
            // Use default file names
            $defaultFiles = [
                'landlords' => 'ACTIVE LANDLORD DETAILS JAN 2026.xlsx',
                'properties' => 'ACTIVE PROPERTIES JAN 2026.xlsx',
                'units' => 'ACTIVE UNITS JAN 2026.xlsx',
                'tenants' => 'tenant  details.xlsx',
                'staff' => 'ACTIVE STAFFS JAN 2026.xlsx',
            ];

            foreach ($defaultFiles as $type => $filename) {
                $fullPath = $defaultBasePath . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($fullPath)) {
                    $paths[$type] = $fullPath;
                }
            }
        } else {
            // Use individual options
            if ($this->option('landlords')) {
                $paths['landlords'] = $this->option('landlords');
            }
            if ($this->option('properties')) {
                $paths['properties'] = $this->option('properties');
            }
            if ($this->option('units')) {
                $paths['units'] = $this->option('units');
            }
            if ($this->option('tenants')) {
                $paths['tenants'] = $this->option('tenants');
            }
            if ($this->option('staff')) {
                $paths['staff'] = $this->option('staff');
            }
        }

        return $paths;
    }

    /**
     * Display import results
     */
    protected function displayResults(array $result): void
    {
        $this->newLine();
        $this->info('=== IMPORT COMPLETE ===');
        $this->newLine();

        // Stats table
        $this->table(
            ['Entity', 'Imported', 'Failed'],
            [
                ['Landlords', $result['stats']['landlords']['imported'], $result['stats']['landlords']['failed']],
                ['Properties', $result['stats']['properties']['imported'], $result['stats']['properties']['failed']],
                ['Units', $result['stats']['units']['imported'], $result['stats']['units']['failed']],
                ['Tenants', $result['stats']['tenants']['imported'], $result['stats']['tenants']['failed']],
                ['Leases', $result['stats']['leases']['imported'], $result['stats']['leases']['failed']],
                ['Staff', $result['stats']['staff']['imported'], $result['stats']['staff']['failed']],
            ],
        );

        $this->newLine();
        $this->info("Started: {$result['started_at']}");
        $this->info("Completed: {$result['completed_at']}");
        $this->info("Duration: {$result['duration']}");

        // Display errors if any
        if (! empty($result['errors'])) {
            $this->newLine();
            $this->warn('=== ERRORS ENCOUNTERED ===');
            $this->newLine();

            $errorGroups = [];
            foreach ($result['errors'] as $error) {
                $errorGroups[$error['type']][] = $error;
            }

            foreach ($errorGroups as $type => $errors) {
                $errorCount = count($errors);
                $this->error("{$type} ({$errorCount} errors):");
                foreach (array_slice($errors, 0, 10) as $error) { // Show first 10 errors per type
                    $rowInfo = isset($error['row']) ? "Row {$error['row']}" : 'General';
                    $message = is_array($error['message']) ? json_encode($error['message']) : $error['message'];
                    $this->line("  {$rowInfo}: {$message}");
                }
                if ($errorCount > 10) {
                    $this->line('  ... and ' . ($errorCount - 10) . ' more errors');
                }
                $this->newLine();
            }
        } else {
            $this->newLine();
            $this->info('✓ No errors encountered!');
        }

        // Summary
        $this->newLine();
        $totalImported = array_sum(array_column($result['stats'], 'imported'));
        $totalFailed = array_sum(array_column($result['stats'], 'failed'));

        if ($this->option('dry-run')) {
            $this->info("DRY RUN: Would import {$totalImported} records ({$totalFailed} would fail)");
        } else {
            $this->info("Successfully imported {$totalImported} records ({$totalFailed} failed)");
        }
    }
}
