<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseDocumentEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Lease $lease,
        protected string $customMessage = '',
        protected bool $attachPdf = true,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Lease Document - ' . ($this->lease->reference_number ?? 'Chabrin Lease'))
            ->greeting('Dear ' . ($notifiable->full_name ?? 'Tenant') . ',')
            ->line('Please find details of your lease with Chabrin Agencies:')
            ->line('')
            ->line('**Lease Reference:** ' . ($this->lease->reference_number ?? 'N/A'))
            ->line('**Property:** ' . ($this->lease->property?->name ?? 'N/A'))
            ->line('**Unit:** ' . ($this->lease->unit?->unit_number ?? 'N/A'))
            ->line('**Monthly Rent:** KES ' . number_format((float) ($this->lease->monthly_rent ?? 0), 2))
            ->line('**Status:** ' . ucwords(str_replace('_', ' ', $this->lease->workflow_state ?? 'N/A')));

        if ($this->lease->start_date) {
            $mail->line('**Start Date:** ' . $this->lease->start_date->format('d/m/Y'));
        }

        if ($this->lease->end_date) {
            $mail->line('**End Date:** ' . $this->lease->end_date->format('d/m/Y'));
        }

        if ($this->customMessage) {
            $mail->line('')
                ->line('---')
                ->line($this->customMessage);
        }

        $mail->line('')
            ->line('If you have any questions, please contact your field officer or the Chabrin office.')
            ->salutation('Regards, Chabrin Agencies');

        // Attach lease PDF if available
        if ($this->attachPdf) {
            $pdfPath = storage_path("app/leases/{$this->lease->id}/lease.pdf");
            if (file_exists($pdfPath)) {
                $mail->attach($pdfPath, [
                    'as' => 'Lease_' . ($this->lease->reference_number ?? $this->lease->id) . '.pdf',
                    'mime' => 'application/pdf',
                ]);
            }
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lease_id' => $this->lease->id,
            'reference_number' => $this->lease->reference_number,
            'action' => 'document_emailed',
        ];
    }
}
