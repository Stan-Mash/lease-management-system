<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residential Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 30px 40px;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .header .company-name {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header .address-line {
            font-size: 10pt;
            margin: 2px 0;
        }

        .document-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 30px 0 25px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .parties-section {
            margin: 20px 0;
        }

        .party-label {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin: 15px 0;
        }

        .party-details {
            margin-left: 20px;
            margin-bottom: 20px;
        }

        .field-group {
            margin: 8px 0;
        }

        .field-label {
            font-weight: bold;
            display: inline-block;
            min-width: 150px;
        }

        .field-value {
            display: inline;
            border-bottom: 1px dotted #333;
            padding: 0 5px;
        }

        .premises-section {
            margin: 25px 0;
            text-align: center;
        }

        .premises-section .title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 15px;
        }

        .agreement-intro {
            text-align: justify;
            margin: 20px 0;
            text-indent: 30px;
        }

        .agreement-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin: 25px 0 20px 0;
            text-transform: uppercase;
        }

        .clause-section {
            margin: 15px 0;
        }

        .clause-number {
            font-weight: bold;
            margin-right: 10px;
        }

        .clause-content {
            text-align: justify;
            margin-left: 30px;
            margin-bottom: 10px;
        }

        .sub-clause {
            margin: 10px 0 10px 50px;
            text-align: justify;
        }

        .sub-clause-letter {
            font-weight: bold;
            margin-right: 10px;
            display: inline-block;
            min-width: 20px;
        }

        .schedule-section {
            margin: 25px 0;
            page-break-before: auto;
        }

        .schedule-title {
            font-weight: bold;
            font-size: 13pt;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .schedule-item {
            margin: 12px 0;
            padding-left: 20px;
        }

        .schedule-label {
            font-weight: bold;
            display: inline-block;
            min-width: 180px;
        }

        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .signature-block {
            margin: 30px 0;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin-top: 50px;
        }

        .signature-label {
            font-weight: bold;
            margin-top: 5px;
        }

        .date-field {
            margin-top: 10px;
            font-size: 10pt;
        }

        .page-number {
            text-align: center;
            font-size: 9pt;
            margin-top: 20px;
        }

        .qr-code {
            text-align: right;
            margin: 20px 0;
        }

        .qr-code img {
            width: 100px;
            height: 100px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="company-name">NACICO PLAZA, LANDHIES ROAD</div>
        <div class="address-line">5<sup>TH</sup> FLOOR – ROOM 517</div>
        <div class="address-line">P.O. Box 16659 – 00620</div>
        <div class="address-line">NAIROBI</div>
        <div class="address-line">CELL: +254-720-854-389</div>
        <div class="address-line">MAIL: info@chabrinagencies.co.ke</div>
    </div>

    {{-- Document Title --}}
    <div class="document-title">
        TENANCY LEASE AGREEMENT
    </div>

    {{-- Parties Section --}}
    <div class="parties-section">
        <div class="party-label">BETWEEN</div>

        <div class="party-details">
            <div class="field-group">
                <span class="field-label">1.</span>
                <span class="field-value">{{ $landlord->name ?? '__________________' }}</span> c/o
            </div>
            <div class="field-group" style="margin-left: 20px;">
                <strong>MANAGING AGENT: CHABRIN AGENCIES LTD</strong>
            </div>
            <div class="field-group" style="margin-left: 20px;">
                P O BOX 16659-00620<br>
                NAIROBI
            </div>
        </div>

        <div class="party-label">AND</div>

        <div class="party-details">
            <div class="field-group">
                <span class="field-label">2. TENANT:</span>
                <span class="field-value">{{ $tenant->full_name }}</span>
            </div>
            <div class="field-group">
                <span class="field-label">ID NO:</span>
                <span class="field-value">{{ $tenant->id_number }}</span>
                <span style="margin-left: 20px;">Tel:</span>
                <span class="field-value">{{ $tenant->phone }}</span>
            </div>
            <div class="field-group">
                <span class="field-label">ADDRESS:</span>
                <span class="field-value">{{ $tenant->address ?? 'N/A' }}</span>
            </div>
            <div class="field-group">
                <span class="field-label">NEXT OF KIN:</span>
                <span class="field-value">{{ $tenant->next_of_kin_name ?? 'N/A' }}</span>
                <span style="margin-left: 20px;">Tel:</span>
                <span class="field-value">{{ $tenant->next_of_kin_phone ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    {{-- Premises Section --}}
    <div class="premises-section">
        <div class="title">IN RESPECT OF RESIDENTIAL PREMISES DESIGNED AS:</div>
        <div class="field-group">
            <span class="field-label">PLOT NO:</span>
            <span class="field-value">{{ $property->plot_number ?? 'N/A' }}</span>
            <span style="margin-left: 40px;" class="field-label">Flat no:</span>
            <span class="field-value">{{ $unit->unit_number }}</span>
        </div>
    </div>

    {{-- Agreement Introduction --}}
    <div class="agreement-intro">
        This tenancy agreement is made on the <strong>{{ $lease->start_date->format('d') }}</strong> /
        <strong>{{ $lease->start_date->format('m') }}</strong> /
        <strong>{{ $lease->start_date->format('Y') }}</strong>
        between <strong>{{ $landlord->name ?? '__________________' }}</strong> c/o CHABRIN AGENCIES LTD of Post Office number
        16659-00620 Nairobi In the Republic of Kenya (herein called "the managing agent" which
        expression shall where the context so admits include its successors and assigns) of the
        one part and <strong>{{ $tenant->full_name }}</strong> of ID No <strong>{{ $tenant->id_number }}</strong> Post Office
        number <strong>{{ $tenant->postal_address ?? 'N/A' }}</strong> (Hereafter called "the tenant" which expression shall where
        the context so admits include his/her personal representatives and assigns) of the other
        part.
    </div>

    {{-- Main Agreement Clauses --}}
    <div class="agreement-title">NOW THIS TENANCY AGREEMENT WITNESSES AS FOLLOWS:</div>

    <div class="clause-section">
        <div class="clause-content">
            <span class="clause-number">1.</span> That landlord hereby grants and the tenant hereby accepts a lease of the premises
            (hereinafter called the "premises") described in the schedule hereto for the term of
            and at the rent specified in the said schedule, payable as provided in the said
            schedule subject to the covenants agreements conditions, stipulations and provisions
            contained hereinafter.
        </div>
    </div>

    <div class="clause-section">
        <div class="clause-content">
            <span class="clause-number">2.</span> The tenants covenants with the landlord as follows:-
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">a.</span>
            To pay the rent as stated in the schedule without any deductions whatsoever
            to the landlord or the landlord's duly appointed agents.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">b.</span>
            On or before execution of this agreement to pay the landlord or his agents
            Kenya Shillings <strong>{{ number_format($lease->deposit_amount, 2) }}</strong> Refundable security bond to be held
            by the said landlord or his agent until this agreement is terminated. The said
            deposit shall be refunded to the tenant without interest on termination of this
            agreement after the due performance of all the terms and conditions of this
            agreement by the tenant to the satisfaction of the landlord. Should the tenant
            default in such performance, the said deposit will be utilized by the landlord in
            performance in the said terms and conditions on behalf of the tenant.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">c.</span>
            The tenant has examined and knows the condition of premises and has
            received the same in good order and repairs except as herein otherwise
            specified at the execution of this lease and upon the termination of this lease
            in any way, tenant will immediately yield up premises to Lessor or his Agent in
            as good condition as when the same as entered upon by tenant and in
            particular the tenant shall be required to repaint the interior walls and fittings
            with first quality paint to restore them as they were at the commencement of
            the tenancy. The repainting and repair shall be carried by a contractor
            approved and appointed by the Lessor or his agent.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">d.</span>
            To pay all electricity and water conservancy charges in respect of the said
            premises throughout the terms hereby created or to the date of its sooner
            termination as hereinafter provided.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">e.</span>
            To keep the interior of the said premises including all doors, windows, locks,
            fasteners, keys, water taps and all internal sanitary apparatus and electric light
            fittings in good and tenantable repair and proper working order and condition
            (fair wear and tear expected).
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">f.</span>
            Not to make alterations in or additions to the said premise without the
            landlord's consent in writing.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">g.</span>
            Not to assign, sublet or part with or share possession of the said premises or
            any part thereof during the currency of this tenancy.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">h.</span>
            To use the premises for residential purposes only and not carry on any trade,
            business or profession in the said premises.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">i.</span>
            Not to do or permit to be done on the said premises anything which may be or
            become a nuisance, annoyance or disturbance to the landlord or to tenants or
            occupiers of the adjoining or neighbouring premises.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">j.</span>
            To permit the landlord or his agents at all reasonable times to enter and view
            the state and condition of the premises and of all additions and improvements
            thereto.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">k.</span>
            Not to keep pets within the said premises without prior written consent of the
            landlord.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">l.</span>
            To give to the landlord One (1) calendar month's notice in writing of intention
            to terminate this tenancy.
        </div>
    </div>

    {{-- Schedule Section --}}
    <div style="page-break-before: always;"></div>

    <div class="schedule-section">
        <div class="schedule-title">THE SCHEDULE ABOVE REFERRED TO</div>

        <div class="schedule-item">
            <span class="schedule-label">1. The Premises:</span>
            <span>{{ $property->name ?? 'N/A' }}, Unit {{ $unit->unit_number }}</span>
        </div>

        <div class="schedule-item">
            <span class="schedule-label">2. Plot Number:</span>
            <span>{{ $property->plot_number ?? 'N/A' }}</span>
        </div>

        <div class="schedule-item">
            <span class="schedule-label">3. Term:</span>
            <span>
                From <strong>{{ $lease->start_date->format('d/m/Y') }}</strong>
                to <strong>{{ $lease->end_date->format('d/m/Y') }}</strong>
            </span>
        </div>

        <div class="schedule-item">
            <span class="schedule-label">4. Rent:</span>
            <span>Kenya Shillings <strong>{{ number_format($lease->monthly_rent, 2) }}</strong> per month</span>
        </div>

        <div class="schedule-item">
            <span class="schedule-label">5. Payment Terms:</span>
            <span>Rent payable on or before the 5th day of each month</span>
        </div>

        <div class="schedule-item">
            <span class="schedule-label">6. Security Deposit:</span>
            <span>Kenya Shillings <strong>{{ number_format($lease->deposit_amount, 2) }}</strong></span>
        </div>

        <div class="schedule-item">
            <span class="schedule-label">7. Tenant Details:</span>
            <span>{{ $tenant->full_name }}, ID: {{ $tenant->id_number }}</span>
        </div>

        <div class="schedule-item">
            <span class="schedule-label">8. Landlord Details:</span>
            <span>{{ $landlord->name ?? 'N/A' }}</span>
        </div>
    </div>

    {{-- Signature Section --}}
    <div class="signature-section">
        <div class="signature-block">
            <div><strong>SIGNED by the Landlord/Agent:</strong></div>
            <div class="signature-line"></div>
            <div class="signature-label">{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</div>
            <div class="date-field">Date: ___________________</div>
        </div>

        <div class="signature-block">
            <div><strong>SIGNED by the Tenant:</strong></div>
            <div class="signature-line"></div>
            <div class="signature-label">{{ $tenant->full_name }}</div>
            <div class="date-field">Date: ___________________</div>
            <div class="date-field">ID Number: {{ $tenant->id_number }}</div>
        </div>
    </div>

    {{-- QR Code --}}
    @if(isset($qrCode) && $qrCode)
    <div class="qr-code">
        <img src="{{ $qrCode }}" alt="Lease QR Code">
        <div style="font-size: 9pt; margin-top: 5px;">
            Ref: {{ $lease->reference_number }}
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="page-number">
        <em>This is a legally binding document. Please read carefully before signing.</em>
    </div>
</body>
</html>
