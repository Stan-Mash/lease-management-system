<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to Zone Manager when a tenant disputes a lease.
 *
 * This notification is triggered when a tenant rejects a lease through
 * the tenant portal, requiring admin intervention to resolve.
 */
class LeaseDisputedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Lease $lease;

    public string $reason;

    public ?string $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Lease $lease, string $reason, ?string $comment = null)
    {
        $this->lease = $lease;
        $this->reason = $reason;
        $this->comment = $comment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Lease Disputed - ' . $this->lease->reference_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A tenant has raised a dispute regarding their lease agreement.')
            ->line('**Dispute Details:**')
            ->line('Reference: **' . $this->lease->reference_number . '**')
            ->line('Tenant: **' . $this->lease->tenant->name . '**')
            ->line('Phone: **' . $this->lease->tenant->phone . '**')
            ->line('Reason: **' . $this->getReasonLabel() . '**');

        if ($this->comment) {
            $mail->line('Tenant Comment: *"' . $this->comment . '"*');
        }

        $mail->line('**Current Lease Terms:**')
            ->line('Monthly Rent: **KES ' . number_format($this->lease->monthly_rent, 2) . '**')
            ->line('Lease Period: **' . $this->lease->start_date->format('d M Y') . ' - ' . $this->lease->end_date->format('d M Y') . '**')
            ->action('Review & Resolve Dispute', url('/admin/leases/' . $this->lease->id))
            ->line('Please review the dispute and take appropriate action to resolve it.')
            ->line('You can edit the lease terms and re-send it to the tenant for signature.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'lease_id' => $this->lease->id,
            'reference_number' => $this->lease->reference_number,
            'tenant_id' => $this->lease->tenant_id,
            'tenant_name' => $this->lease->tenant->name,
            'tenant_phone' => $this->lease->tenant->phone,
            'reason' => $this->reason,
            'reason_label' => $this->getReasonLabel(),
            'comment' => $this->comment,
            'monthly_rent' => $this->lease->monthly_rent,
            'action' => 'lease_disputed',
            'disputed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get human-readable label for the dispute reason.
     */
    protected function getReasonLabel(): string
    {
        return match ($this->reason) {
            'rent_too_high' => 'Rent Amount Too High',
            'wrong_dates' => 'Incorrect Lease Dates',
            'incorrect_details' => 'Incorrect Personal/Property Details',
            'terms_disagreement' => 'Disagreement with Terms & Conditions',
            'not_my_lease' => 'This is Not My Lease',
            'other' => 'Other Reason',
            default => ucfirst(str_replace('_', ' ', $this->reason)),
        };
    }
}
