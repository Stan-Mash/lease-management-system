<?php

namespace App\Mail;

use App\Models\RentEscalation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentEscalationLandlordNotice extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public RentEscalation $escalation,
    ) {}

    public function envelope(): Envelope
    {
        $effectiveDate = $this->escalation->effective_date->format('d M Y');

        return new Envelope(
            subject: "Rent Escalation Confirmation - Effective {$effectiveDate}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rent-escalation-landlord',
            with: [
                'escalation' => $this->escalation,
                'lease' => $this->escalation->lease,
                'landlord' => $this->escalation->lease->landlord,
                'tenant' => $this->escalation->lease->tenant,
                'property' => $this->escalation->lease->unit?->property?->name ?? 'Property',
                'unit' => $this->escalation->lease->unit?->unit_number ?? '',
                'effectiveDate' => $this->escalation->effective_date->format('d M Y'),
            ],
        );
    }
}
