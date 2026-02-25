<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the responsible user (field officer, zone manager, or admin) when a
 * lease's 72-hour signing link has expired without the tenant completing signature.
 */
class LeaseSigningLinkExpiredNotification extends Notification implements ShouldQueue
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
        $reference = $this->lease->reference_number ?? 'N/A';
        $tenantName = $this->lease->tenant?->names ?? 'Tenant';
        $leaseUrl = url('/admin/leases/' . $this->lease->id);

        return (new MailMessage)
            ->subject("Signing link expired — Lease {$reference}")
            ->greeting('Hello,')
            ->line("The 72-hour signing link for lease **{$reference}** ({$tenantName}) has expired without the tenant completing the signature process.")
            ->line('')
            ->line('You can resend a new signing link from the lease view page.')
            ->action('View Lease', $leaseUrl)
            ->line('')
            ->salutation('Chabrin Lease Management System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'action' => 'signing_link_expired',
            'lease_id' => $this->lease->id,
            'reference' => $this->lease->reference_number,
            'tenant' => $this->lease->tenant?->names,
        ];
    }
}
