<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Exception;
use Filament\Pages\Page;
use FilesystemIterator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnitEnum;

class SystemPulse extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'System Pulse';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'system-pulse';

    protected string $view = 'filament.pages.system-pulse';

    public function getTitle(): string
    {
        return 'System Pulse';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdmin());
    }

    public function getViewData(): array
    {
        return [
            'queueStats' => $this->getQueueStats(),
            'failedJobs' => $this->getFailedJobs(),
            'systemHealth' => $this->getSystemHealth(),
            'recentActivity' => $this->getRecentActivity(),
            'storageStats' => $this->getStorageStats(),
            'databaseStats' => $this->getDatabaseStats(),
        ];
    }

    public function retryFailedJob(string $uuid): void
    {
        \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$uuid]]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Job queued for retry',
        ]);
    }

    public function retryAllFailedJobs(): void
    {
        \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => ['all']]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'All failed jobs queued for retry',
        ]);
    }

    public function flushFailedJobs(): void
    {
        \Illuminate\Support\Facades\Artisan::call('queue:flush');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'All failed jobs cleared',
        ]);
    }

    protected function getQueueStats(): array
    {
        $queues = ['default', 'high', 'low', 'notifications', 'sms'];
        $stats = [];

        foreach ($queues as $queue) {
            $pendingCount = DB::table('jobs')
                ->where('queue', $queue)
                ->count();

            $stats[] = [
                'name' => ucfirst($queue),
                'pending' => $pendingCount,
                'status' => $pendingCount > 100 ? 'critical' : ($pendingCount > 50 ? 'warning' : 'healthy'),
            ];
        }

        // Total across all queues
        $totalPending = DB::table('jobs')->count();

        return [
            'queues' => $stats,
            'totalPending' => $totalPending,
            'status' => $totalPending > 500 ? 'critical' : ($totalPending > 100 ? 'warning' : 'healthy'),
        ];
    }

    protected function getFailedJobs(): array
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $commandName = $payload['displayName'] ?? 'Unknown';

                return [
                    'id' => $job->uuid ?? $job->id,
                    'job' => class_basename($commandName),
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception' => \Illuminate\Support\Str::limit($job->exception, 200),
                ];
            })
            ->toArray();

        $totalFailed = DB::table('failed_jobs')->count();
        $last24Hours = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDay())
            ->count();

        return [
            'jobs' => $failedJobs,
            'total' => $totalFailed,
            'last24Hours' => $last24Hours,
            'status' => $last24Hours > 10 ? 'critical' : ($last24Hours > 0 ? 'warning' : 'healthy'),
        ];
    }

    protected function getSystemHealth(): array
    {
        $checks = [];

        // Database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'healthy', 'message' => 'Connected'];
        } catch (Exception $e) {
            $checks['database'] = ['status' => 'critical', 'message' => 'Connection failed'];
        }

        // Cache connection
        try {
            Cache::put('health_check', true, 10);
            Cache::forget('health_check');
            $checks['cache'] = ['status' => 'healthy', 'message' => 'Working'];
        } catch (Exception $e) {
            $checks['cache'] = ['status' => 'critical', 'message' => 'Not working'];
        }

        // Storage
        try {
            $storagePath = storage_path('app');
            if (is_writable($storagePath)) {
                $checks['storage'] = ['status' => 'healthy', 'message' => 'Writable'];
            } else {
                $checks['storage'] = ['status' => 'critical', 'message' => 'Not writable'];
            }
        } catch (Exception $e) {
            $checks['storage'] = ['status' => 'critical', 'message' => 'Error checking'];
        }

        // Queue worker status (check if jobs are being processed)
        $oldestJob = DB::table('jobs')->orderBy('created_at', 'asc')->first();
        if ($oldestJob) {
            $jobAge = now()->diffInMinutes(\Carbon\Carbon::createFromTimestamp($oldestJob->created_at));
            if ($jobAge > 30) {
                $checks['queue_worker'] = [
                    'status' => 'critical',
                    'message' => "Jobs waiting {$jobAge}+ mins - worker may be down",
                ];
            } elseif ($jobAge > 10) {
                $checks['queue_worker'] = [
                    'status' => 'warning',
                    'message' => "Jobs waiting {$jobAge} mins",
                ];
            } else {
                $checks['queue_worker'] = ['status' => 'healthy', 'message' => 'Processing normally'];
            }
        } else {
            $checks['queue_worker'] = ['status' => 'healthy', 'message' => 'No pending jobs'];
        }

        // Overall status
        $criticalCount = collect($checks)->where('status', 'critical')->count();
        $warningCount = collect($checks)->where('status', 'warning')->count();

        return [
            'checks' => $checks,
            'overall' => $criticalCount > 0 ? 'critical' : ($warningCount > 0 ? 'warning' : 'healthy'),
        ];
    }

    protected function getRecentActivity(): array
    {
        // Get recent job batches if available
        $recentBatches = [];
        if (DB::getSchemaBuilder()->hasTable('job_batches')) {
            $recentBatches = DB::table('job_batches')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($batch) {
                    return [
                        'name' => $batch->name,
                        'total_jobs' => $batch->total_jobs,
                        'pending_jobs' => $batch->pending_jobs,
                        'failed_jobs' => $batch->failed_jobs,
                        'progress' => $batch->total_jobs > 0
                            ? round((($batch->total_jobs - $batch->pending_jobs) / $batch->total_jobs) * 100)
                            : 100,
                        'created_at' => $batch->created_at,
                        'finished_at' => $batch->finished_at,
                    ];
                })
                ->toArray();
        }

        // Get recent processed jobs count (approximation from jobs table)
        $jobsProcessedLast5Min = Cache::get('jobs_processed_last_5min', 0);
        $jobsProcessedLastHour = Cache::get('jobs_processed_last_hour', 0);

        return [
            'batches' => $recentBatches,
            'throughput' => [
                'last5min' => $jobsProcessedLast5Min,
                'lastHour' => $jobsProcessedLastHour,
            ],
        ];
    }

    protected function getStorageStats(): array
    {
        $storagePath = storage_path('app');

        // Get disk usage
        $totalSpace = disk_total_space($storagePath);
        $freeSpace = disk_free_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;
        $usedPercentage = round(($usedSpace / $totalSpace) * 100, 1);

        // Get document storage size
        $documentSize = 0;
        $documentPath = storage_path('app/lease-documents');
        if (is_dir($documentPath)) {
            $documentSize = $this->getDirectorySize($documentPath);
        }

        return [
            'total' => $this->formatBytes($totalSpace),
            'used' => $this->formatBytes($usedSpace),
            'free' => $this->formatBytes($freeSpace),
            'usedPercentage' => $usedPercentage,
            'documents' => $this->formatBytes($documentSize),
            'status' => $usedPercentage > 90 ? 'critical' : ($usedPercentage > 75 ? 'warning' : 'healthy'),
        ];
    }

    protected function getDatabaseStats(): array
    {
        $tables = [
            'leases' => DB::table('leases')->count(),
            'tenants' => DB::table('tenants')->count(),
            'properties' => DB::table('properties')->count(),
            'units' => DB::table('units')->count(),
            'users' => DB::table('users')->count(),
            'lease_documents' => DB::table('lease_documents')->count(),
        ];

        // Get database size (PostgreSQL)
        $dbSize = 0;

        try {
            $result = DB::select('SELECT pg_database_size(current_database()) as size');
            $dbSize = $result[0]->size ?? 0;
        } catch (Exception $e) {
            // Silently fail if not PostgreSQL
        }

        return [
            'tables' => $tables,
            'totalRecords' => array_sum($tables),
            'databaseSize' => $this->formatBytes($dbSize),
        ];
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    protected function formatBytes(int|float $bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
