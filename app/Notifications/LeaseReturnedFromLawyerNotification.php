<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use App\Models\LeaseLawyerTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the zone manager (or admins) when an advocate returns
 * the signed/stamped lease via the lawyer portal.
 */
class LeaseReturnedFromLawyerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Lease $lease,
        protected LeaseLawyerTracking $tracking,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ref          = $this->lease->reference_number ?? 'N/A';
        $tenantName   = $this->lease->tenant?->names ?? 'Tenant';
        $propertyName = $this->lease->property?->property_name ?? 'N/A';
        $lawyerName   = $this->tracking->lawyer?->name ?? 'the advocate';
        $adminName    = $notifiable->name ?? 'Manager';

        return (new MailMessage)
            ->subject("Advocate has returned the signed lease — {$ref}")
            ->greeting("Dear {$adminName},")
            ->line("The signed/stamped lease has been returned by {$lawyerName} via the lawyer portal and is ready for your review.")
            ->line('')
            ->line("**Lease Reference:** {$ref}")
            ->line("**Tenant:** {$tenantName}")
            ->line("**Property:** {$propertyName}")
            ->line('')
            ->line('The signed document has been saved to the Document Vault. Please log in to the Chabrin admin system, open the lease, verify the document, and countersign to activate the lease.')
            ->line('')
            ->salutation('Regards, Chabrin Agencies');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lease_id'         => $this->lease->id,
            'reference_number' => $this->lease->reference_number,
            'action'           => 'lawyer_returned_lease',
        ];
    }
}
