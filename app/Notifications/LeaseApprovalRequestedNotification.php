<?php

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseApprovalRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Lease $lease;

    /**
     * Create a new notification instance.
     */
    public function __construct(Lease $lease)
    {
        $this->lease = $lease;
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
        return (new MailMessage)
            ->subject('New Lease Awaiting Your Approval - ' . $this->lease->reference_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new lease agreement has been prepared and is awaiting your approval.')
            ->line('**Lease Details:**')
            ->line('Reference: **' . $this->lease->reference_number . '**')
            ->line('Tenant: **' . $this->lease->tenant->name . '**')
            ->line('Property Type: **' . ucfirst($this->lease->lease_type) . '**')
            ->line('Monthly Rent: **' . number_format($this->lease->monthly_rent, 2) . ' ' . ($this->lease->currency ?? 'KES') . '**')
            ->line('Lease Period: **' . $this->lease->start_date->format('d M Y') . ' - ' . $this->lease->end_date->format('d M Y') . '**')
            ->action('Review & Approve Lease', url('/admin/leases/' . $this->lease->id))
            ->line('Please review the lease details and take appropriate action.')
            ->line('Thank you for your prompt attention.');
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
            'tenant_name' => $this->lease->tenant->name,
            'monthly_rent' => $this->lease->monthly_rent,
            'action' => 'approval_requested',
        ];
    }
}
