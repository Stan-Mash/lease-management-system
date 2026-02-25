<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lease;
use App\Helpers\LocaleHelper;
use App\Models\User;
use App\Notifications\LeaseSigningLinkExpiredNotification;
use App\Services\SMSService;
use App\Services\TenantEventService;
use Illuminate\Console\Command;

/**
 * Alert when lease signing links have expired in the last hour.
 * Runs hourly; notifies responsible user and tenant (SMS) and logs TenantEvent.
 */
class AlertExpiredSigningLinksCommand extends Command
{
    protected $signature = 'app:alert-expired-signing-links
                            {--dry-run : List affected leases without sending alerts}';

    protected $description = 'Notify responsible users and tenants when signing links expired in the last hour';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $leases = Lease::query()
            ->with(['tenant', 'assignedFieldOfficer', 'assignedZone.zoneManager'])
            ->whereIn('workflow_state', ['sent_digital', 'pending_otp'])
            ->whereNotNull('signing_link_expires_at')
            ->where('signing_link_expires_at', '<', now())
            ->where('signing_link_expires_at', '>', now()->subHour())
            ->whereNull('signing_link_expired_alerted_at')
            ->get();

        if ($leases->isEmpty()) {
            $this->info('No expired signing links in the last hour.');

            return self::SUCCESS;
        }

        $this->info('Found ' . $leases->count() . ' lease(s) with expired signing links.');

        foreach ($leases as $lease) {
            $this->line('  — ' . $lease->reference_number . ' (' . ($lease->tenant?->names ?? 'N/A') . ')');

            if ($dryRun) {
                continue;
            }

            if ($lease->tenant) {
                TenantEventService::logSystem(
                    $lease->tenant,
                    'Signing link expired',
                    ['description' => 'The 72-hour signing link expired without the tenant completing the signature process.'],
                    $lease,
                );
            }

            $responsible = $lease->assignedFieldOfficer
                ?? $lease->assignedZone?->zoneManager
                ?? User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))->first();

            if ($responsible) {
                $responsible->notify(new LeaseSigningLinkExpiredNotification($lease));
            }

            if ($lease->tenant?->mobile_number) {
                $phone = config('lease.contact_phone', config('app.contact_phone', ''));
                $message = LocaleHelper::forTenant($lease->tenant, 'sms_link_expired', [
                    'phone' => $phone ?: 'your Chabrin contact',
                ]);
                SMSService::send($lease->tenant->mobile_number, $message, [
                    'type' => 'signing_link_expired',
                    'reference' => $lease->reference_number,
                ]);
            }

            $lease->update(['signing_link_expired_alerted_at' => now()]);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
