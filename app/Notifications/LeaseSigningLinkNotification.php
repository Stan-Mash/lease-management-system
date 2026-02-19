<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseSigningLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Lease $lease,
        protected string $signingLink,
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
        $startDate = $this->lease->start_date?->format('d/m/Y') ?? 'N/A';

        return (new MailMessage)
            ->subject("Action Required: Sign Your Lease — {$reference}")
            ->greeting("Dear {$tenantName},")
            ->line('Your lease with Chabrin Agencies has been approved and is ready for your digital signature.')
            ->line('')
            ->line("**Lease Reference:** {$reference}")
            ->line("**Property:** {$propertyName}")
            ->line("**Unit:** {$unitNumber}")
            ->line("**Monthly Rent:** KES {$monthlyRent}")
            ->line("**Start Date:** {$startDate}")
            ->line('')
            ->action('Sign Lease Now', $this->signingLink)
            ->line('This link is valid for **72 hours**. When you open it, you will be asked to:')
            ->line('1. Request a 6-digit OTP code sent to your phone')
            ->line('2. Verify the OTP and review the full lease')
            ->line('3. Draw your digital signature')
            ->line('')
            ->line('If you did not request this or have any questions, please contact Chabrin Agencies immediately.')
            ->salutation('Regards, Chabrin Agencies');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lease_id' => $this->lease->id,
            'reference_number' => $this->lease->reference_number,
            'action' => 'signing_link_sent',
        ];
    }
}
