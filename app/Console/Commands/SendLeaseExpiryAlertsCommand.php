<?php

namespace App\Console\Commands;

use App\Models\Lease;
use App\Models\User;
use App\Services\SMSService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeaseExpiryAlertsCommand extends Command
{
    private const ALERT_DAYS = [90, 60, 30];

    protected $signature = 'leases:send-expiry-alerts
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send alerts for leases expiring in 90, 60, and 30 days';

    public function handle(SMSService $smsService): int
    {
        $this->info('Checking for expiring leases...');
        $dryRun = $this->option('dry-run');

        $totalAlerts = 0;

        foreach (self::ALERT_DAYS as $days) {
            $count = $this->processExpiringLeases($days, $smsService, $dryRun);
            $totalAlerts += $count;
        }

        $this->info("Total alerts sent: {$totalAlerts}");

        return self::SUCCESS;
    }

    private function processExpiringLeases(int $days, SMSService $smsService, bool $dryRun): int
    {
        $targetDate = now()->addDays($days)->format('Y-m-d');

        $leases = Lease::query()
            ->where('status', 'active')
            ->whereDate('end_date', $targetDate)
            ->with(['tenant', 'landlord', 'unit.property', 'zone'])
            ->get();

        if ($leases->isEmpty()) {
            $this->line("  No leases expiring in {$days} days.");

            return 0;
        }

        $this->info("Found {$leases->count()} leases expiring in {$days} days.");

        $alertsSent = 0;

        foreach ($leases as $lease) {
            if ($dryRun) {
                $this->line("  [DRY RUN] Would alert for lease {$lease->reference_number}");
                $alertsSent++;
                continue;
            }

            try {
                $this->sendAlerts($lease, $days, $smsService);
                $alertsSent++;
                $this->line("  Sent alert for lease {$lease->reference_number}");
            } catch (Exception $e) {
                $this->error("  Failed to send alert for {$lease->reference_number}: {$e->getMessage()}");
                Log::error('Lease expiry alert failed', [
                    'lease_id' => $lease->id,
                    'days' => $days,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $alertsSent;
    }

    private function sendAlerts(Lease $lease, int $days, SMSService $smsService): void
    {
        $urgency = match ($days) {
            90 => 'Reminder',
            60 => 'Important',
            30 => 'URGENT',
            default => 'Notice',
        };

        // Notify tenant
        if ($lease->tenant?->phone) {
            $message = $this->buildTenantMessage($lease, $days, $urgency);
            $smsService->send($lease->tenant->phone, $message);
        }

        // Notify tenant by email if preferred
        if ($lease->tenant?->email && in_array($lease->tenant->notification_preference ?? 'sms', ['email', 'both'])) {
            $this->sendTenantEmail($lease, $days, $urgency);
        }

        // Notify zone manager
        $this->notifyZoneManager($lease, $days);

        // Log the alert
        $lease->auditLogs()->create([
            'action' => 'expiry_alert_sent',
            'changes' => [
                'days_until_expiry' => $days,
                'urgency' => $urgency,
                'tenant_notified' => (bool) $lease->tenant?->phone,
            ],
            'performed_by' => null,
        ]);
    }

    private function buildTenantMessage(Lease $lease, int $days, string $urgency): string
    {
        $property = $lease->unit?->property?->name ?? 'your property';
        $unit = $lease->unit?->unit_number ?? '';
        $endDate = $lease->end_date->format('d M Y');

        return "[{$urgency}] Your lease for {$property} {$unit} expires on {$endDate} ({$days} days). "
            . "Contact Chabrin Agencies to discuss renewal. Ref: {$lease->reference_number}";
    }

    private function sendTenantEmail(Lease $lease, int $days, string $urgency): void
    {
        // Simple email - could be expanded to use Mailable class
        Mail::raw(
            $this->buildTenantEmailBody($lease, $days, $urgency),
            function ($message) use ($lease, $urgency, $days) {
                $message->to($lease->tenant->email)
                    ->subject("[{$urgency}] Lease Expiring in {$days} Days - {$lease->reference_number}");
            },
        );
    }

    private function buildTenantEmailBody(Lease $lease, int $days, string $urgency): string
    {
        $property = $lease->unit?->property?->name ?? 'your property';
        $unit = $lease->unit?->unit_number ?? '';
        $endDate = $lease->end_date->format('d M Y');

        return <<<EOT
Dear {$lease->tenant->name},

This is a {$urgency} reminder that your lease is expiring soon.

Lease Details:
- Reference: {$lease->reference_number}
- Property: {$property} {$unit}
- Expiry Date: {$endDate}
- Days Remaining: {$days}

Please contact Chabrin Agencies to discuss renewal options.

Best regards,
Chabrin Agencies
EOT;
    }

    private function notifyZoneManager(Lease $lease, int $days): void
    {
        if (! $lease->zone_id) {
            return;
        }

        $zoneManager = User::where('zone_id', $lease->zone_id)
            ->where('role', 'zone_manager')
            ->first();

        if ($zoneManager?->email) {
            Mail::raw(
                "Lease {$lease->reference_number} expires in {$days} days. Tenant: {$lease->tenant?->name}",
                function ($message) use ($zoneManager, $days) {
                    $message->to($zoneManager->email)
                        ->subject("Lease Expiry Alert - {$days} Days");
                },
            );
        }
    }
}
