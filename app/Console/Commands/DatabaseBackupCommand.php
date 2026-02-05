<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup
                            {--connection= : Database connection to backup (default: pgsql)}
                            {--compress : Compress the backup file with gzip}
                            {--retention=30 : Days to keep backups}
                            {--disk=local : Storage disk for backups}';

    protected $description = 'Create a backup of the PostgreSQL database with retention management';

    protected string $backupPath = 'backups/database';

    public function handle(): int
    {
        $connection = $this->option('connection') ?: config('database.default');
        $compress = $this->option('compress');
        $retentionDays = (int) $this->option('retention');
        $disk = $this->option('disk');

        $this->info("Starting database backup for connection: {$connection}");

        // Get database configuration
        $dbConfig = config("database.connections.{$connection}");

        if (!$dbConfig) {
            $this->error("Database connection '{$connection}' not found.");
            return Command::FAILURE;
        }

        // Create backup filename
        $timestamp = now()->format('Y-m-d_H-i-s');
        $database = $dbConfig['database'];
        $filename = "backup_{$database}_{$timestamp}.sql";

        if ($compress) {
            $filename .= '.gz';
        }

        // Ensure backup directory exists
        Storage::disk($disk)->makeDirectory($this->backupPath);

        $fullPath = Storage::disk($disk)->path("{$this->backupPath}/{$filename}");

        // Build pg_dump command
        $command = $this->buildPgDumpCommand($dbConfig, $fullPath, $compress);

        $this->line("Executing backup command...");

        // Execute backup
        $result = $this->executeBackup($command, $dbConfig);

        if ($result !== 0) {
            $this->error("Backup failed with exit code: {$result}");
            return Command::FAILURE;
        }

        // Verify backup was created
        if (!file_exists($fullPath)) {
            $this->error("Backup file was not created.");
            return Command::FAILURE;
        }

        $fileSize = $this->formatBytes(filesize($fullPath));
        $this->info("Backup created successfully: {$filename} ({$fileSize})");

        // Log backup details
        $this->logBackup($filename, $fullPath, $database);

        // Clean up old backups
        $this->cleanOldBackups($disk, $retentionDays);

        // Display backup summary
        $this->displayBackupSummary($disk);

        return Command::SUCCESS;
    }

    protected function buildPgDumpCommand(array $dbConfig, string $outputPath, bool $compress): string
    {
        $host = $dbConfig['host'] ?? 'localhost';
        $port = $dbConfig['port'] ?? 5432;
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];

        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -F p -b -v %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database)
        );

        if ($compress) {
            $command .= ' | gzip';
        }

        $command .= ' > ' . escapeshellarg($outputPath);

        return $command;
    }

    protected function executeBackup(string $command, array $dbConfig): int
    {
        // Set PGPASSWORD environment variable
        $env = ['PGPASSWORD' => $dbConfig['password'] ?? ''];

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorspec, $pipes, null, $env);

        if (is_resource($process)) {
            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            if ($this->output->isVerbose() && $stdout) {
                $this->line($stdout);
            }

            if ($exitCode !== 0 && $stderr) {
                $this->error($stderr);
            }

            return $exitCode;
        }

        return 1;
    }

    protected function logBackup(string $filename, string $fullPath, string $database): void
    {
        $logEntry = [
            'timestamp' => now()->toIso8601String(),
            'filename' => $filename,
            'path' => $fullPath,
            'database' => $database,
            'size' => filesize($fullPath),
            'checksum' => hash_file('sha256', $fullPath),
        ];

        $logPath = storage_path('logs/database-backups.log');
        $logLine = json_encode($logEntry) . PHP_EOL;

        file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
    }

    protected function cleanOldBackups(string $disk, int $retentionDays): void
    {
        $this->line("Cleaning backups older than {$retentionDays} days...");

        $files = Storage::disk($disk)->files($this->backupPath);
        $cutoffDate = now()->subDays($retentionDays);
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk($disk)->lastModified($file);
            $fileDate = \Carbon\Carbon::createFromTimestamp($lastModified);

            if ($fileDate->isBefore($cutoffDate)) {
                Storage::disk($disk)->delete($file);
                $deletedCount++;
                $this->line("Deleted old backup: " . basename($file));
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup(s).");
        } else {
            $this->line("No old backups to clean.");
        }
    }

    protected function displayBackupSummary(string $disk): void
    {
        $files = Storage::disk($disk)->files($this->backupPath);
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += Storage::disk($disk)->size($file);
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Backups', count($files)],
                ['Total Size', $this->formatBytes($totalSize)],
                ['Storage Disk', $disk],
                ['Backup Path', $this->backupPath],
            ]
        );
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
