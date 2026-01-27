<?php

namespace App\Mail;

use App\Models\RentEscalation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentEscalationTenantNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RentEscalation $escalation,
    ) {}

    public function envelope(): Envelope
    {
        $effectiveDate = $this->escalation->effective_date->format('d M Y');

        return new Envelope(
            subject: "Rent Adjustment Notice - Effective {$effectiveDate}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rent-escalation-tenant',
            with: [
                'escalation' => $this->escalation,
                'lease' => $this->escalation->lease,
                'tenant' => $this->escalation->lease->tenant,
                'property' => $this->escalation->lease->unit?->property?->name ?? 'your property',
                'unit' => $this->escalation->lease->unit?->unit_number ?? '',
                'effectiveDate' => $this->escalation->effective_date->format('d M Y'),
            ],
        );
    }
}
