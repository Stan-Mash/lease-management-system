<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseSignedConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Lease $lease,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantName = $notifiable->names ?? 'Tenant';
        $reference = $this->lease->reference_number ?? 'N/A';
        $propertyName = $this->lease->property?->property_name ?? 'N/A';
        $unitNumber = $this->lease->unit?->unit_number ?? 'N/A';
        $monthlyRent = number_format((float) ($this->lease->monthly_rent ?? 0), 2);
        $startDate = $this->lease->start_date?->format('d M Y') ?? 'N/A';
        $signedAt = $this->lease->getLatestDigitalSignature()?->signed_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A');

        return (new MailMessage)
            ->subject("Lease Signed Successfully — {$reference}")
            ->greeting("Dear {$tenantName},")
            ->line('Your lease agreement with Chabrin Agencies has been **successfully signed**. This email serves as your confirmation.')
            ->line('')
            ->line("**Lease Reference:** {$reference}")
            ->line("**Property:** {$propertyName}")
            ->line("**Unit:** {$unitNumber}")
            ->line("**Monthly Rent:** KES {$monthlyRent}")
            ->line("**Start Date:** {$startDate}")
            ->line("**Signed On:** {$signedAt}")
            ->line('')
            ->line('**What happens next?**')
            ->line('- Your property manager will review the signed lease')
            ->line('- You will be contacted regarding the security deposit and any remaining steps')
            ->line('- Please retain this email for your records')
            ->line('')
            ->line('If you did not sign this lease or believe this is an error, please contact Chabrin Agencies immediately at support@chabrin.com')
            ->salutation('Regards, Chabrin Agencies');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lease_id' => $this->lease->id,
            'reference_number' => $this->lease->reference_number,
            'action' => 'lease_signed',
        ];
    }
}
