<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the property manager (zone manager or super admin) when a tenant
 * completes their digital signature on a lease. Prompts them to review and
 * countersign so the tenant can receive their copy and the lease goes ACTIVE.
 */
class LeaseTenantSignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Lease $lease,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantName   = $this->lease->tenant?->names ?? 'The tenant';
        $reference    = $this->lease->reference_number ?? 'N/A';
        $propertyName = $this->lease->property?->property_name ?? 'N/A';
        $unitNumber   = $this->lease->unit?->unit_number ?? 'N/A';
        $signedAt     = now()->format('d M Y, h:i A');

        $leaseUrl = url('/admin/leases/' . $this->lease->id);

        return (new MailMessage)
            ->subject("Action Required: {$tenantName} has signed lease {$reference}")
            ->greeting('Hello,')
            ->line("{$tenantName} has completed their digital signature on lease **{$reference}**.")
            ->line('')
            ->line("Property: {$propertyName}")
            ->line("Unit: {$unitNumber}")
            ->line("Signed at: {$signedAt}")
            ->line('')
            ->line('Please log in to the Chabrin system, review the signed lease, and countersign to activate it. The tenant will automatically receive their copy once the lease is activated.')
            ->line('')
            ->line("Lease reference: {$reference}")
            ->salutation('Chabrin Lease Management System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lease_id'         => $this->lease->id,
            'reference_number' => $this->lease->reference_number,
            'tenant_name'      => $this->lease->tenant?->names,
            'property_name'    => $this->lease->property?->property_name,
            'unit_number'      => $this->lease->unit?->unit_number,
            'action'           => 'tenant_signed',
            'message'          => ($this->lease->tenant?->names ?? 'A tenant') . ' has signed lease ' . $this->lease->reference_number . '. Countersign required.',
        ];
    }
}
