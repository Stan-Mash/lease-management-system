<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatabaseRestoreCommand extends Command
{
    protected $signature = 'db:restore
                            {file? : Backup file to restore (optional, will show list if not provided)}
                            {--connection= : Database connection to restore to (default: pgsql)}
                            {--force : Skip confirmation prompt}
                            {--disk=local : Storage disk where backups are stored}';

    protected $description = 'Restore the PostgreSQL database from a backup file';

    protected string $backupPath = 'backups/database';

    public function handle(): int
    {
        $connection = $this->option('connection') ?: config('database.default');
        $disk = $this->option('disk');
        $force = $this->option('force');

        // Get available backups
        $backups = $this->getAvailableBackups($disk);

        if (empty($backups)) {
            $this->error('No backup files found.');

            return Command::FAILURE;
        }

        // Select backup file
        $file = $this->argument('file');

        if (! $file) {
            $file = $this->selectBackup($backups);
            if (! $file) {
                return Command::FAILURE;
            }
        }

        // Validate file exists
        $fullPath = Storage::disk($disk)->path("{$this->backupPath}/{$file}");

        if (! file_exists($fullPath)) {
            $this->error("Backup file not found: {$file}");

            return Command::FAILURE;
        }

        // Get database configuration
        $dbConfig = config("database.connections.{$connection}");

        if (! $dbConfig) {
            $this->error("Database connection '{$connection}' not found.");

            return Command::FAILURE;
        }

        // Confirm restore
        if (! $force) {
            $this->warn('WARNING: This will overwrite the current database!');
            $this->line("Database: {$dbConfig['database']}");
            $this->line("Backup file: {$file}");

            if (! $this->confirm('Are you sure you want to restore this backup?')) {
                $this->info('Restore cancelled.');

                return Command::SUCCESS;
            }
        }

        $this->info("Restoring database from: {$file}");

        // Build and execute restore command
        $result = $this->executeRestore($fullPath, $dbConfig);

        if ($result !== 0) {
            $this->error("Restore failed with exit code: {$result}");

            return Command::FAILURE;
        }

        $this->info('Database restored successfully!');

        // Log restore
        $this->logRestore($file, $dbConfig['database']);

        return Command::SUCCESS;
    }

    protected function getAvailableBackups(string $disk): array
    {
        $files = Storage::disk($disk)->files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if (str_starts_with($filename, 'backup_') && (str_ends_with($filename, '.sql') || str_ends_with($filename, '.sql.gz'))) {
                $backups[] = [
                    'filename' => $filename,
                    'size' => $this->formatBytes(Storage::disk($disk)->size($file)),
                    'date' => \Carbon\Carbon::createFromTimestamp(Storage::disk($disk)->lastModified($file))->format('Y-m-d H:i:s'),
                ];
            }
        }

        // Sort by date descending
        usort($backups, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $backups;
    }

    protected function selectBackup(array $backups): ?string
    {
        $this->info('Available backups:');
        $this->newLine();

        $this->table(
            ['#', 'Filename', 'Size', 'Date'],
            array_map(fn ($backup, $index) => [
                $index + 1,
                $backup['filename'],
                $backup['size'],
                $backup['date'],
            ], $backups, array_keys($backups)),
        );

        $selection = $this->ask('Enter the number of the backup to restore (or "q" to quit)');

        if ($selection === 'q' || $selection === null) {
            return null;
        }

        $index = (int) $selection - 1;

        if (! isset($backups[$index])) {
            $this->error('Invalid selection.');

            return null;
        }

        return $backups[$index]['filename'];
    }

    protected function executeRestore(string $fullPath, array $dbConfig): int
    {
        $host = $dbConfig['host'] ?? 'localhost';
        $port = $dbConfig['port'] ?? 5432;
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];

        $isCompressed = str_ends_with($fullPath, '.gz');

        if ($isCompressed) {
            $command = sprintf(
                'gunzip -c %s | psql -h %s -p %s -U %s -d %s',
                escapeshellarg($fullPath),
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                escapeshellarg($database),
            );
        } else {
            $command = sprintf(
                'psql -h %s -p %s -U %s -d %s -f %s',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($fullPath),
            );
        }

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

            // psql outputs notices to stderr even on success, so only show on failure
            if ($exitCode !== 0 && $stderr) {
                $this->error($stderr);
            }

            return $exitCode;
        }

        return 1;
    }

    protected function logRestore(string $filename, string $database): void
    {
        $logEntry = [
            'timestamp' => now()->toIso8601String(),
            'action' => 'restore',
            'filename' => $filename,
            'database' => $database,
            'user' => get_current_user(),
        ];

        $logPath = storage_path('logs/database-backups.log');
        $logLine = json_encode($logEntry) . PHP_EOL;

        file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
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
