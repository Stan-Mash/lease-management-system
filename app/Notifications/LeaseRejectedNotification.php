<?php

namespace App\Notifications;

use App\Models\Lease;
use App\Models\LeaseApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseRejectedNotification extends Notification implements ShouldQueue
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
            ->subject('Lease Requires Revision - ' . $this->lease->reference_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your lease agreement requires some revisions before it can be approved.')
            ->line('**Lease Details:**')
            ->line('Reference: **' . $this->lease->reference_number . '**')
            ->line('Property Type: **' . ucfirst($this->lease->lease_type) . '**')
            ->line('**Reason for Revision:**')
            ->line('"' . $this->approval->rejection_reason . '"');

        if ($this->approval->comments) {
            $mail->line('**Additional Comments:**')
                ->line('"' . $this->approval->comments . '"');
        }

        $mail->line('**Next Steps:**')
            ->line('1. Review the landlord\'s feedback above')
            ->line('2. Contact Chabrin support to discuss required changes')
            ->line('3. We will revise the lease and resubmit for approval')
            ->action('Contact Support', url('/admin/leases/' . $this->lease->id))
            ->line('We apologize for the inconvenience and will work to resolve this quickly.');

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
            'rejection_reason' => $this->approval->rejection_reason,
            'has_comments' => ! empty($this->approval->comments),
            'action' => 'lease_rejected',
        ];
    }
}
