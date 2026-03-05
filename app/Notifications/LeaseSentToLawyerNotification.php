<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lease;
use App\Models\LeaseLawyerTracking;
use App\Models\Lawyer;
use App\Services\LeasePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the lawyer when a lease is sent for review/stamping.
 * Can send either: (1) email with PDF attached, or (2) email with portal link (download + upload stamped PDF).
 */
class LeaseSentToLawyerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Lease $lease,
        protected Lawyer $lawyer,
        protected LeaseLawyerTracking $tracking,
        protected bool $attachPdf = true,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ref = $this->lease->reference_number ?? 'N/A';
        $tenantName = $this->lease->tenant?->names ?? 'Tenant';
        $propertyName = $this->lease->property?->property_name ?? 'N/A';

        $mail = (new MailMessage)
            ->subject("Lease for review and stamping – {$ref}")
            ->greeting('Dear ' . ($this->lawyer->name ?? 'Advocate') . ',')
            ->line('Chabrin Agencies has sent you the following lease for legal review and advocate stamping.')
            ->line('')
            ->line("**Lease reference:** {$ref}")
            ->line("**Tenant:** {$tenantName}")
            ->line("**Property:** {$propertyName}");

        if ($this->tracking->sent_notes) {
            $mail->line('')
                ->line('**Notes:** ' . $this->tracking->sent_notes);
        }

        if ($this->attachPdf) {
            try {
                $pdfService = app(LeasePdfService::class);
                $pdfBinary = $pdfService->generate($this->lease);
                $filename = $pdfService->filename($this->lease);
                $mail->attachData($pdfBinary, $filename, ['mime' => 'application/pdf']);
            } catch (\Throwable $e) {
                report($e);
                $mail->line('')
                    ->line('(The lease PDF could not be attached. Please request it from Chabrin if needed.)');
            }
        } else {
            $portalUrl = $this->tracking->lawyer_link_token
                ? route('lawyer.portal', ['token' => $this->tracking->lawyer_link_token])
                : null;
            if ($portalUrl) {
                $mail->line('')
                    ->line('Use the link below to download the lease PDF. After stamping, upload the stamped PDF using the same link so it is returned to Chabrin.')
                    ->action('Open lease portal (download & upload)', $portalUrl)
                    ->line('This link expires on ' . $this->tracking->lawyer_link_expires_at?->format('d M Y') . '.');
            }
        }

        $mail->line('')
            ->salutation('Regards, Chabrin Agencies');

        return $mail;
    }
}
