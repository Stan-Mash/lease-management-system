<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenancy Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 0.75in 0.75in 0.75in 0.75in;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Calibri', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }

        /* Repeating Header on Each Page */
        .page-header {
            text-align: center;
            font-size: 10pt;
            line-height: 1.3;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #000;
        }

        .page-header .company-name {
            font-weight: bold;
            font-size: 10pt;
        }

        .page-header .floor {
            font-size: 9pt;
        }

        .page-header .contact-line {
            font-size: 9pt;
            margin: 1px 0;
        }

        /* Main Title */
        .document-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 30px 0 25px 0;
            text-transform: uppercase;
        }

        /* Section Dividers */
        .section-divider {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin: 20px 0;
            text-transform: uppercase;
        }

        /* Party Details */
        .party-section {
            margin: 15px 0 15px 20px;
        }

        .party-number {
            font-weight: bold;
            display: inline-block;
            min-width: 30px;
        }

        .field-row {
            margin: 8px 0 8px 20px;
            line-height: 1.6;
        }

        .field-label {
            font-weight: bold;
            display: inline;
        }

        .field-underline {
            display: inline-block;
            border-bottom: 1px dotted #333;
            min-width: 200px;
            padding: 0 5px;
        }

        /* Premises Section */
        .premises-header {
            text-align: center;
            font-weight: bold;
            margin: 20px 0 15px 0;
        }

        .premises-fields {
            text-align: center;
            margin: 10px 0;
        }

        /* Agreement Text */
        .agreement-paragraph {
            text-align: justify;
            margin: 15px 0;
            text-indent: 0;
            line-height: 1.5;
        }

        .clause-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin: 25px 0 20px 0;
            text-transform: uppercase;
        }

        /* Numbered Clauses */
        .clause {
            margin: 12px 0;
            text-align: justify;
        }

        .clause-number {
            font-weight: bold;
            margin-right: 8px;
        }

        .clause-content {
            margin-left: 0px;
        }

        /* Sub-clauses */
        .sub-clauses {
            margin: 10px 0 10px 20px;
        }

        .sub-clause {
            margin: 10px 0;
            text-align: justify;
        }

        .sub-clause-letter {
            font-weight: bold;
            margin-right: 8px;
            display: inline;
        }

        /* Schedule Section */
        .schedule-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin: 30px 0 20px 0;
            text-transform: uppercase;
        }

        .schedule-item {
            margin: 10px 0 10px 20px;
        }

        /* Signatures */
        .signature-section {
            margin-top: 50px;
        }

        .signature-block {
            margin: 30px 0;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin-top: 50px;
            display: inline-block;
        }

        .signature-label {
            font-weight: bold;
            margin-top: 8px;
        }

        .signature-details {
            font-size: 10pt;
            margin-top: 5px;
        }

        /* Page Breaks */
        .page-break {
            page-break-after: always;
        }

        .page-break-before {
            page-break-before: always;
        }

        /* Spacing adjustments to match original */
        strong {
            font-weight: bold;
        }

        sup {
            font-size: 8pt;
            vertical-align: super;
        }
    </style>
</head>
<body>
    {{-- PAGE 1 --}}
    <div class="page-header">
        <div class="company-name">NACICO PLAZA, LANDHIES ROAD</div>
        <div class="floor">5<sup>TH</sup> FLOOR – ROOM 517</div>
        <div class="contact-line">P.O. Box 16659 – 00620</div>
        <div class="contact-line">NAIROBI</div>
        <div class="contact-line">CELL : +254-720-854-389</div>
        <div class="contact-line">MAIL: info@chabrinagencies.co.ke</div>
    </div>

    <div class="document-title">TENANCY LEASE AGREEMENT</div>

    <div class="section-divider">BETWEEN</div>

    <div class="party-section">
        <span class="party-number">1.</span> {{ $landlord->name ?? '______________________________' }} c/o<br>
        <div style="margin-left: 30px; margin-top: 5px;">
            <strong>MANAGING AGENT: CHABRIN AGENCIES LTD</strong><br>
            P O BOX 16659-00620<br>
            NAIROBI
        </div>
    </div>

    <div class="section-divider">AND</div>

    <div class="party-section">
        <span class="party-number">2.</span> <span class="field-label">TENANT:</span>
        <span class="field-underline">{{ $tenant->full_name }}</span>
    </div>

    <div class="field-row">
        <span class="field-label">ID NO:</span> <span class="field-underline">{{ $tenant->id_number }}</span>
        (Attach copy) <span class="field-label">Tel:</span> <span class="field-underline">{{ $tenant->phone }}</span>
    </div>

    <div class="field-row">
        <span class="field-label">ADDRESS:</span> <span class="field-underline">{{ $tenant->address ?? 'N/A' }}</span>
    </div>

    <div class="field-row">
        <span class="field-label">NEXT OF KIN:</span> <span class="field-underline">{{ $tenant->next_of_kin_name ?? 'N/A' }}</span>
        <span class="field-label">Tel:</span> <span class="field-underline">{{ $tenant->next_of_kin_phone ?? 'N/A' }}</span>
    </div>

    <div class="premises-header">IN RESPECT OF RESIDENTIAL PREMISES DESIGNED AS:</div>

    <div class="premises-fields">
        <span class="field-label">PLOT NO:</span> <span class="field-underline">{{ $property->plot_number ?? 'N/A' }}</span>
        <span class="field-label" style="margin-left: 40px;">Flat no:</span> <span class="field-underline">{{ $unit->unit_number }}</span>
    </div>

    <div class="agreement-paragraph">
        This tenancy agreement is made on the <strong>{{ $lease->start_date->format('d') }}</strong> / <strong>{{ $lease->start_date->format('m') }}</strong> / <strong>{{ $lease->start_date->format('Y') }}</strong>
        between <strong>{{ $landlord->name ?? '______________________________' }}</strong> c/o CHABRIN AGENCIES LTD of Post Office number
        16659-00620 Nairobi In the Republic of Kenya (herein called "the managing agent" which
        expression shall where the context so admits include its successors and assigns) of the
        one part and <strong>{{ $tenant->full_name }}</strong> of ID No <strong>{{ $tenant->id_number }}</strong> Post Office
        number <strong>{{ $tenant->postal_address ?? 'N/A' }}</strong> (Hereafter called "the tenant" which expression shall where
        the context so admits include his/her personal representatives and assigns) of the other
        part.
    </div>

    {{-- PAGE 2 --}}
    <div class="page-break"></div>

    <div class="page-header">
        <div class="company-name">NACICO PLAZA, LANDHIES ROAD</div>
        <div class="floor">5<sup>TH</sup> FLOOR – ROOM 517</div>
        <div class="contact-line">P.O. Box 16659 – 00620</div>
        <div class="contact-line">NAIROBI</div>
        <div class="contact-line">CELL : +254-720-854-389</div>
        <div class="contact-line">MAIL: info@chabrinagencies.co.ke</div>
    </div>

    <div class="clause-title">NOW THIS TENANCY AGREEMENT WITNESSES AS FOLLOWS:</div>

    <div class="clause">
        <span class="clause-number">1.</span>
        <span class="clause-content">
            That landlord hereby grants and the tenant hereby accepts a lease of the premises
            (hereinafter called the "premises") described in the schedule hereto for the term of
            and at the rent specified in the said schedule, payable as provided in the said
            schedule subject to the covenants agreements conditions, stipulations and provisions
            contained hereinafter.
        </span>
    </div>

    <div class="clause">
        <span class="clause-number">2.</span>
        <span class="clause-content">The tenants covenants with the landlord as follows:-</span>
    </div>

    <div class="sub-clauses">
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
            landlord's prior consent in writing.
        </div>
    </div>

    {{-- PAGE 3 --}}
    <div class="page-break"></div>

    <div class="page-header">
        <div class="company-name">NACICO PLAZA, LANDHIES ROAD</div>
        <div class="floor">5<sup>TH</sup> FLOOR – ROOM 517</div>
        <div class="contact-line">P.O. Box 16659 – 00620</div>
        <div class="contact-line">NAIROBI</div>
        <div class="contact-line">CELL : +254-720-854-389</div>
        <div class="contact-line">MAIL: info@chabrinagencies.co.ke</div>
    </div>

    <div class="sub-clauses" style="margin-top: 20px;">
        <div class="sub-clause">
            <span class="sub-clause-letter">g.</span>
            Not without the landlord's prior consent in writing to alter or interfere with the
            plumbing or electrical installations other than to keep in repair and to replace
            as and when necessary all switches fuses and elements forming part of the
            electrical installations.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">h.</span>
            To replace and be responsible for the cost of any keys which are damaged or
            lost and their appropriate interior and exterior doors and locks.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">i.</span>
            To permit the landlord or the landlord's agent to enter and view the condition
            of the said premises and upon notice given by the landlord forthwith to repair
            in accordance with such notice and in the event of the tenant not carrying
            out such repairs within fourteen days of the said notice the cost shall be a debt
            due from the landlord and shall be forthwith recoverable by action as rent.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">j.</span>
            To use the premises as a residential premises for the tenant only.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">k.</span>
            Not to permit any sale by auction to be held upon the said premises.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">l.</span>
            Not to suffer any part of the said premises to be used as to cause annoyance
            or inconvenience to the occupiers of the adjacent or neighboring flat or
            premises.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">m.</span>
            Not to suffer any part of the said premises to be used for any illegal purpose.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">n.</span>
            Not to assign underlet or part with possession of any part of the said premises
            without the prior consent in writing or the landlord, first had and obtained.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">o.</span>
            During the last one (1) months of the term hereby created to permit the
            landlord to affix upon the said premises a notice for re-letting and to permit
            persons with authority from the landlord or the landlord's agent or agents at
            reasonable times to view the said premises by prior appointment.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">p.</span>
            To yield up the said premises with all fixtures (other than the tenant's fixtures)
            and additions at the expiration or sooner determination of the tenancy in good
            and tenantable repair and condition and good as the tenant found them at
            the commencement of the lease.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">q.</span>
            In case of breach of this tenancy agreement the tenant or the landlord is
            entitled to one month's notice in writing or paying one month rent in lieu
            thereof to terminate the term hereby created.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">r.</span>
            To pay service charge e.g. security and garbage collection. The responsibility
            to appoint agents of these services rest on tenants unless where the landlord is
            requested to assist.
        </div>
    </div>

    {{-- PAGE 4 --}}
    <div class="page-break"></div>

    <div class="page-header">
        <div class="company-name">NACICO PLAZA, LANDHIES ROAD</div>
        <div class="floor">5<sup>TH</sup> FLOOR – ROOM 517</div>
        <div class="contact-line">P.O. Box 16659 – 00620</div>
        <div class="contact-line">NAIROBI</div>
        <div class="contact-line">CELL : +254-720-854-389</div>
        <div class="contact-line">MAIL: info@chabrinagencies.co.ke</div>
    </div>

    <div class="sub-clauses" style="margin-top: 20px;">
        <div class="sub-clause">
            <span class="sub-clause-letter">s.</span>
            All payments are strictly made to our accounts as provided. Personal cheques
            are not acceptable. Any cheque returned to us unpaid will attract an
            immediate penalty of Kshs 3,500.
        </div>
    </div>

    <div class="clause" style="margin-top: 20px;">
        <span class="clause-number">3.</span>
        <span class="clause-content">The landlord covenant with the tenant as follows:</span>
    </div>

    <div class="sub-clauses">
        <div class="sub-clause">
            <span class="sub-clause-letter">a.</span>
            To permit the tenant to peacefully hold and enjoy the said premises during the
            said term without any interruption by the landlord or any person or agents
            rightfully claiming under or in trust of the landlord, so long as the tenant pays
            the rent hereby reserved and performs and observes the several covenants
            and the conditions herein contained.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">b.</span>
            To keep the walls, roof and structure of the premises in good and tenantable
            state of repair and maintenance.
        </div>

        <div class="sub-clause">
            <span class="sub-clause-letter">c.</span>
            To keep adequately lighted, cleaned and in good state the repair and
            condition the entrance halls and all common area of the said premises.
        </div>
    </div>

    <div class="clause">
        <span class="clause-number">4.</span>
        <span class="clause-content">
            The landlord shall have a right of re-entry and possession if any rent shall not have
            been paid as agreed or on breach or non-observance by the tenant of any covenant
            herein contained or on bankruptcy or composition with creditors or suffering distress
            or execution. In that event this agreement shall stand terminated automatically,
            without prejudice to landlord's rights under this agreement.
        </span>
    </div>

    <div class="clause">
        <span class="clause-number">5.</span>
        <span class="clause-content">
            In case of the premises being required for statutory duties or re-construction the
            landlord shall give the tenant notice not more than six months from the date of
            service.
        </span>
    </div>

    <div class="clause">
        <span class="clause-number">6.</span>
        <span class="clause-content">
            Any party hereto wishing to terminate the tenancy created hereby shall serve upon
            the other party written notice of his/her intention to do so and such notice shall be for
            a period of one (1) calendar month. Rent will be payable to and inclusive of the date
            stipulated in the notice.
        </span>
    </div>

    <div class="clause">
        <span class="clause-number">7.</span>
        <span class="clause-content">
            The landlord shall not be liable for loss or damage to any property of the tenant in
            the said premises from any cause whatsoever.
        </span>
    </div>

    {{-- PAGE 5 - Schedule and Signatures --}}
    <div class="page-break"></div>

    <div class="page-header">
        <div class="company-name">NACICO PLAZA, LANDHIES ROAD</div>
        <div class="floor">5<sup>TH</sup> FLOOR – ROOM 517</div>
        <div class="contact-line">P.O. Box 16659 – 00620</div>
        <div class="contact-line">NAIROBI</div>
        <div class="contact-line">CELL : +254-720-854-389</div>
        <div class="contact-line">MAIL: info@chabrinagencies.co.ke</div>
    </div>

    <div class="schedule-title">THE SCHEDULE ABOVE REFERRED TO</div>

    <div class="schedule-item">
        <strong>THE PREMISES:</strong> {{ $property->name ?? 'N/A' }}, Unit/Flat No. {{ $unit->unit_number }}
    </div>

    <div class="schedule-item">
        <strong>PLOT NUMBER:</strong> {{ $property->plot_number ?? 'N/A' }}
    </div>

    <div class="schedule-item">
        <strong>THE TERM:</strong> From {{ $lease->start_date->format('d/m/Y') }} to {{ $lease->end_date->format('d/m/Y') }}
    </div>

    <div class="schedule-item">
        <strong>THE RENT:</strong> Kenya Shillings {{ number_format($lease->monthly_rent, 2) }} per month
    </div>

    <div class="schedule-item">
        <strong>PAYMENT TERMS:</strong> Rent payable on or before the 5<sup>th</sup> day of each calendar month
    </div>

    <div class="schedule-item">
        <strong>SECURITY DEPOSIT:</strong> Kenya Shillings {{ number_format($lease->deposit_amount, 2) }}
    </div>

    <div class="signature-section">
        <div class="signature-block">
            <div><strong>SIGNED by the LANDLORD/MANAGING AGENT:</strong></div>
            <div class="signature-line"></div>
            <div class="signature-label">{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</div>
            <div class="signature-details">Date: ___________________</div>
        </div>

        <div class="signature-block">
            <div><strong>SIGNED by the TENANT:</strong></div>
            <div class="signature-line"></div>
            <div class="signature-label">{{ $tenant->full_name }}</div>
            <div class="signature-details">ID Number: {{ $tenant->id_number }}</div>
            <div class="signature-details">Date: ___________________</div>
        </div>
    </div>
</body>
</html>
