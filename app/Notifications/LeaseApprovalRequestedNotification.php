<?php

namespace App\Notifications;

use App\Models\Lease;
use App\Helpers\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent synchronously (not queued) so delivery can be confirmed immediately.
 * Landlord approval emails must not silently fail in a queue worker.
 */
class LeaseApprovalRequestedNotification extends Notification
{

    public Lease $lease;
    public string $approvalUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(Lease $lease, string $approvalUrl = '')
    {
        $this->lease       = $lease;
        $this->approvalUrl = $approvalUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $landlordName = $notifiable->names ?? $notifiable->name ?? 'Landlord';
        $tenantName   = $this->lease->tenant->names ?? $this->lease->tenant->name ?? 'Unknown';
        $rent         = Money::format((string) $this->lease->monthly_rent, 'KES');
        $period       = $this->lease->start_date->format('d M Y') . ' to ' . $this->lease->end_date->format('d M Y');
        $actionUrl    = $this->approvalUrl;

        return (new MailMessage)
            ->subject('[Action Required] Lease ' . $this->lease->reference_number . ' needs your approval')
            ->greeting('Dear ' . $landlordName . ',')
            ->line('Chabrin Agencies has prepared a new lease agreement for your property and it requires your approval before proceeding.')
            ->line('**Lease Reference:** ' . $this->lease->reference_number)
            ->line('**Tenant:** ' . $tenantName)
            ->line('**Monthly Rent:** ' . $rent)
            ->line('**Lease Period:** ' . $period)
            ->action('Review and Approve Lease', $actionUrl)
            ->line('This link expires in 7 days. If you have questions, contact us at ' . config('mail.from.address') . '.');
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
            'tenant_name' => $this->lease->tenant->names ?? $this->lease->tenant->name ?? 'Unknown',
            'monthly_rent' => $this->lease->monthly_rent,
            'action' => 'approval_requested',
        ];
    }
}
