<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemPulseWidget extends Widget
{
    protected string $view = 'filament.widgets.system-pulse-widget';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdmin());
    }

    public function getViewData(): array
    {
        return [
            'stats' => $this->getQuickStats(),
        ];
    }

    protected function getQuickStats(): array
    {
        return Cache::remember('system_pulse_quick_stats', now()->addMinutes(2), function () {
            // Queue depth
            $queueDepth = DB::table('jobs')->count();

            // Failed jobs in last 24 hours
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            // Check if queue worker is running (jobs not stuck)
            $workerStatus = 'healthy';
            $oldestJob = DB::table('jobs')->orderBy('created_at', 'asc')->first();
            if ($oldestJob) {
                $jobAge = now()->diffInMinutes(\Carbon\Carbon::createFromTimestamp($oldestJob->created_at));
                if ($jobAge > 30) {
                    $workerStatus = 'critical';
                } elseif ($jobAge > 10) {
                    $workerStatus = 'warning';
                }
            }

            // Overall status
            $overallStatus = 'healthy';
            if ($failedJobs > 10 || $workerStatus === 'critical') {
                $overallStatus = 'critical';
            } elseif ($failedJobs > 0 || $workerStatus === 'warning' || $queueDepth > 100) {
                $overallStatus = 'warning';
            }

            return [
                'queueDepth' => $queueDepth,
                'failedJobs' => $failedJobs,
                'workerStatus' => $workerStatus,
                'overallStatus' => $overallStatus,
            ];
        });
    }
}
