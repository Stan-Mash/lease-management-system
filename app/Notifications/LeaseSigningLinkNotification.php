<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
        $tenantName = $this->lease->tenant->names ?? $this->lease->tenant->name ?? 'Tenant';
        $reference = $this->lease->reference_number;
        $propertyName = $this->lease->property->name ?? 'N/A';
        $unitNumber = $this->lease->unit->unit_code ?? 'N/A';
        $monthlyRent = number_format($this->lease->monthly_rent, 2);
        $startDate = $this->lease->start_date->format('d/m/Y');

        return (new MailMessage)
            ->subject("Your Lease is Ready to Sign — {$reference}")
            ->greeting("Dear {$tenantName},")
            ->line('Your lease with Chabrin Agencies has been approved and is ready for your digital signature.')
            ->line('')
            ->line("**Lease Reference:** {$reference}")
            ->line("**Property:** {$propertyName}")
            ->line("**Unit:** {$unitNumber}")
            ->line("**Monthly Rent:** KES {$monthlyRent}")
            ->line("**Start Date:** {$startDate}")
            ->line('')
            ->action('Review and Sign Lease', $this->signingLink)
            ->line('')
            ->line('This link is valid for **72 hours**. When you open it, you will be asked to:')
            ->line('1. Request a 6-digit OTP code sent to your phone')
            ->line('2. Verify the OTP and review the full lease')
            ->line('3. Draw your digital signature')
            ->line('')
            ->line('If you did not request this or have questions: info@chabrinagencies.co.ke | +254720854389, +254745912688.')
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
