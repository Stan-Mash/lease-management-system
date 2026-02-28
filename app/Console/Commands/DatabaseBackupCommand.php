<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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

        if (! $dbConfig) {
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

        $this->line('Executing backup command...');

        // Execute backup — password handled via secure .pgpass temp file internally
        $result = $this->executeBackup($dbConfig, $fullPath, $compress);

        if ($result !== 0) {
            $this->error("Backup failed with exit code: {$result}");

            return Command::FAILURE;
        }

        // Verify backup was created
        if (! file_exists($fullPath)) {
            $this->error('Backup file was not created.');

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

    protected function buildPgDumpCommand(array $dbConfig, string $pgpassFile, string $outputPath, bool $compress): string
    {
        $host = $dbConfig['host'] ?? 'localhost';
        $port = $dbConfig['port'] ?? 5432;
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];

        // Use PGPASSFILE env var pointing to a temp .pgpass file instead of
        // PGPASSWORD, which would expose the password to all child processes
        // and make it visible in /proc/<pid>/environ on Linux.
        $command = sprintf(
            'PGPASSFILE=%s pg_dump -h %s -p %s -U %s -F p -b -v %s',
            escapeshellarg($pgpassFile),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
        );

        if ($compress) {
            $command .= ' | gzip';
        }

        $command .= ' > ' . escapeshellarg($outputPath);

        return $command;
    }

    /**
     * Write a temporary .pgpass file and return its path.
     *
     * Format: hostname:port:database:username:password
     * The file is chmod 0600 — PostgreSQL refuses to read it otherwise.
     * Caller is responsible for deleting it in a finally block.
     */
    protected function writePgPassFile(array $dbConfig): string
    {
        $host = $dbConfig['host'] ?? 'localhost';
        $port = $dbConfig['port'] ?? 5432;
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'] ?? '';

        // Escape colons and backslashes inside the password field per pgpass spec
        $escapedPassword = str_replace(['\\', ':'], ['\\\\', '\\:'], $password);

        $line = implode(':', [$host, $port, $database, $username, $escapedPassword]);

        $path = tempnam(sys_get_temp_dir(), 'pgpass_');
        file_put_contents($path, $line . PHP_EOL);
        chmod($path, 0600); // Owner-readable only — required by PostgreSQL

        return $path;
    }

    protected function executeBackup(array $dbConfig, string $outputPath, bool $compress): int
    {
        // Write a temporary .pgpass file so the password is never passed via
        // environment variable (which leaks into /proc/<pid>/environ on Linux).
        $pgpassFile = $this->writePgPassFile($dbConfig);

        try {
            $command = $this->buildPgDumpCommand($dbConfig, $pgpassFile, $outputPath, $compress);

            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (! is_resource($process)) {
                return 1;
            }

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
        } finally {
            // Always clean up — even on exception or early return
            if (file_exists($pgpassFile)) {
                unlink($pgpassFile);
            }
        }
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
                $this->line('Deleted old backup: ' . basename($file));
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup(s).");
        } else {
            $this->line('No old backups to clean.');
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
            ],
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
