<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DataCleanupCommand extends Command
{
    protected $signature = 'data:cleanup
                            {--type= : Type of data to clean (otp, audit, document-audit, all)}
                            {--days=30 : Delete records older than this many days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old data based on retention policies';

    public function handle(): int
    {
        $type = $this->option('type') ?: 'all';
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        $this->info("Starting data cleanup (retention: {$days} days)...");
        $this->newLine();

        $results = [];

        switch ($type) {
            case 'otp':
                $results['otp'] = $this->cleanupOtpRecords($days, $dryRun);
                break;
            case 'audit':
                $results['audit'] = $this->cleanupAuditLogs($days, $dryRun);
                break;
            case 'document-audit':
                $results['document-audit'] = $this->cleanupDocumentAuditTrail($days, $dryRun);
                break;
            case 'all':
                $results['otp'] = $this->cleanupOtpRecords($days, $dryRun);
                $results['audit'] = $this->cleanupAuditLogs($days, $dryRun);
                $results['document-audit'] = $this->cleanupDocumentAuditTrail($days, $dryRun);
                $results['temp-files'] = $this->cleanupTempFiles($days, $dryRun);
                break;
            default:
                $this->error("Unknown cleanup type: {$type}");
                return Command::FAILURE;
        }

        $this->displaySummary($results, $dryRun);

        return Command::SUCCESS;
    }

    protected function cleanupOtpRecords(int $days, bool $dryRun): array
    {
        $this->line('Cleaning up OTP records...');

        $cutoffDate = now()->subDays($days);

        $count = DB::table('otp_verifications')
            ->where('created_at', '<', $cutoffDate)
            ->count();

        if (!$dryRun && $count > 0) {
            DB::table('otp_verifications')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
        }

        $this->info("  - OTP records: {$count} " . ($dryRun ? 'would be deleted' : 'deleted'));

        return [
            'table' => 'otp_verifications',
            'count' => $count,
            'cutoff' => $cutoffDate->toDateTimeString(),
        ];
    }

    protected function cleanupAuditLogs(int $days, bool $dryRun): array
    {
        $this->line('Cleaning up audit logs...');

        $cutoffDate = now()->subDays($days);

        // Check if table exists
        if (!$this->tableExists('lease_audit_logs')) {
            $this->warn('  - lease_audit_logs table does not exist');
            return ['table' => 'lease_audit_logs', 'count' => 0, 'cutoff' => $cutoffDate->toDateTimeString()];
        }

        $count = DB::table('lease_audit_logs')
            ->where('created_at', '<', $cutoffDate)
            ->count();

        if (!$dryRun && $count > 0) {
            // Archive before deleting (optional)
            $this->archiveBeforeDelete('lease_audit_logs', $cutoffDate, $days);

            DB::table('lease_audit_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
        }

        $this->info("  - Lease audit logs: {$count} " . ($dryRun ? 'would be deleted' : 'deleted'));

        return [
            'table' => 'lease_audit_logs',
            'count' => $count,
            'cutoff' => $cutoffDate->toDateTimeString(),
        ];
    }

    protected function cleanupDocumentAuditTrail(int $days, bool $dryRun): array
    {
        $this->line('Cleaning up document audit trail...');

        $cutoffDate = now()->subDays($days);

        // Check if table exists
        if (!$this->tableExists('document_audits')) {
            $this->warn('  - document_audits table does not exist');
            return ['table' => 'document_audits', 'count' => 0, 'cutoff' => $cutoffDate->toDateTimeString()];
        }

        $count = DB::table('document_audits')
            ->where('created_at', '<', $cutoffDate)
            ->count();

        if (!$dryRun && $count > 0) {
            // Archive before deleting
            $this->archiveBeforeDelete('document_audits', $cutoffDate, $days);

            DB::table('document_audits')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
        }

        $this->info("  - Document audit trail: {$count} " . ($dryRun ? 'would be deleted' : 'deleted'));

        return [
            'table' => 'document_audits',
            'count' => $count,
            'cutoff' => $cutoffDate->toDateTimeString(),
        ];
    }

    protected function cleanupTempFiles(int $days, bool $dryRun): array
    {
        $this->line('Cleaning up temporary files...');

        $tempPath = storage_path('app/public/temp-uploads');
        $count = 0;

        if (!is_dir($tempPath)) {
            return ['table' => 'temp-uploads', 'count' => 0, 'cutoff' => now()->subDays($days)->toDateTimeString()];
        }

        $cutoffTimestamp = now()->subDays($days)->timestamp;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTimestamp) {
                if (!$dryRun) {
                    unlink($file->getRealPath());
                }
                $count++;
            }
        }

        $this->info("  - Temp files: {$count} " . ($dryRun ? 'would be deleted' : 'deleted'));

        return [
            'table' => 'temp-uploads',
            'count' => $count,
            'cutoff' => now()->subDays($days)->toDateTimeString(),
        ];
    }

    protected function archiveBeforeDelete(string $table, \Carbon\Carbon $cutoffDate, int $days): void
    {
        // Only archive if more than 1000 records are being deleted
        $count = DB::table($table)->where('created_at', '<', $cutoffDate)->count();

        if ($count < 1000) {
            return;
        }

        $archivePath = storage_path("app/backups/archives/{$table}");

        if (!is_dir($archivePath)) {
            mkdir($archivePath, 0755, true);
        }

        $filename = "{$table}_archive_" . now()->format('Y-m-d_H-i-s') . ".json";
        $filePath = "{$archivePath}/{$filename}";

        // Export in chunks to avoid memory issues
        $handle = fopen($filePath, 'w');
        fwrite($handle, "[\n");

        $first = true;
        DB::table($table)
            ->where('created_at', '<', $cutoffDate)
            ->orderBy('created_at')
            ->chunk(500, function ($records) use ($handle, &$first) {
                foreach ($records as $record) {
                    if (!$first) {
                        fwrite($handle, ",\n");
                    }
                    fwrite($handle, json_encode($record));
                    $first = false;
                }
            });

        fwrite($handle, "\n]");
        fclose($handle);

        // Compress the archive
        $gzPath = "{$filePath}.gz";
        $gz = gzopen($gzPath, 'w9');
        gzwrite($gz, file_get_contents($filePath));
        gzclose($gz);
        unlink($filePath);

        $this->line("  - Archived {$count} records to: " . basename($gzPath));
    }

    protected function displaySummary(array $results, bool $dryRun): void
    {
        $this->newLine();
        $this->info('Cleanup Summary:');

        $tableData = [];
        $totalDeleted = 0;

        foreach ($results as $type => $data) {
            $tableData[] = [
                $type,
                $data['table'],
                $data['count'],
                $data['cutoff'],
            ];
            $totalDeleted += $data['count'];
        }

        $this->table(
            ['Type', 'Table/Path', 'Records', 'Cutoff Date'],
            $tableData
        );

        $this->newLine();
        $action = $dryRun ? 'would be cleaned' : 'cleaned';
        $this->info("Total records {$action}: {$totalDeleted}");

        // Log cleanup action
        if (!$dryRun) {
            $this->logCleanup($results, $totalDeleted);
        }
    }

    protected function logCleanup(array $results, int $totalDeleted): void
    {
        $logEntry = [
            'timestamp' => now()->toIso8601String(),
            'action' => 'data_cleanup',
            'total_deleted' => $totalDeleted,
            'details' => $results,
        ];

        $logPath = storage_path('logs/data-cleanup.log');
        $logLine = json_encode($logEntry) . PHP_EOL;

        file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
