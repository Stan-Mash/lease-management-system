<?php

namespace App\Notifications;

use App\Models\Lease;
use App\Models\LeaseApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Lease $lease;

    public LeaseApproval $approval;

    /**
     * Create a new notification instance.
     */
    public function __construct(Lease $lease, LeaseApproval $approval)
    {
        $this->lease = $lease;
        $this->approval = $approval;
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
            ->subject('Lease Approved - ' . $this->lease->reference_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your lease agreement has been approved by the landlord.')
            ->line('**Lease Details:**')
            ->line('Reference: **' . $this->lease->reference_number . '**')
            ->line('Property Type: **' . ucfirst($this->lease->lease_type) . '**')
            ->line('Monthly Rent: **' . number_format($this->lease->monthly_rent, 2) . ' ' . ($this->lease->currency ?? 'KES') . '**')
            ->line('Lease Period: **' . $this->lease->start_date->format('d M Y') . ' - ' . $this->lease->end_date->format('d M Y') . '**');

        if ($this->approval->comments) {
            $mail->line('**Landlord Comments:**')
                ->line('"' . $this->approval->comments . '"');
        }

        $mail->line('**Next Steps:**')
            ->line('1. You will receive a digital signing link shortly')
            ->line('2. Review and sign the lease agreement')
            ->line('3. Pay the security deposit')
            ->line('4. Your lease will then become active')
            ->action('View Lease Details', url('/admin/leases/' . $this->lease->id))
            ->line('Thank you for choosing Chabrin Lease Management.');

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
            'approval_id' => $this->approval->id,
            'monthly_rent' => $this->lease->monthly_rent,
            'has_comments' => ! empty($this->approval->comments),
            'action' => 'lease_approved',
        ];
    }
}
