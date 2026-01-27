<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenancy Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 0.5in 0.75in 0.5in 0.75in;
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

        /* Header with Logo and Contact Info */
        .document-header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            width: 30%;
            vertical-align: top;
        }

        .logo-section {
            text-align: left;
        }

        .logo-image {
            width: 120px;
            height: auto;
        }

        .header-right {
            display: table-cell;
            width: 70%;
            vertical-align: top;
            text-align: right;
            padding-left: 20px;
        }

        .contact-info {
            font-size: 9.5pt;
            line-height: 1.3;
            color: #F7941D; /* Orange color */
            font-weight: 600;
        }

        .contact-line {
            margin: 2px 0;
        }

        .contact-bold {
            font-weight: 700;
        }

        /* Yellow horizontal line */
        .header-separator {
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #F7941D 0%, #FDB913 100%);
            margin: 8px 0 20px 0;
        }

        /* Main Title */
        .document-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 25px 0 20px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }

        /* Section Headers */
        .section-header {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin: 18px 0;
            text-transform: uppercase;
        }

        /* Party Information */
        .party-block {
            margin: 12px 0 12px 25px;
        }

        .party-number {
            font-weight: bold;
            margin-right: 8px;
        }

        .party-detail {
            margin-left: 35px;
            line-height: 1.5;
        }

        /* Form Fields */
        .form-row {
            margin: 8px 0 8px 25px;
            line-height: 1.6;
        }

        .field-label {
            font-weight: bold;
        }

        .field-fill {
            display: inline-block;
            border-bottom: 1px dotted #000;
            min-width: 150px;
            padding: 0 3px;
        }

        /* Premises Section */
        .premises-title {
            text-align: center;
            font-weight: bold;
            margin: 18px 0 12px 0;
        }

        .premises-fields {
            text-align: center;
            margin: 10px 0;
        }

        /* Agreement Text */
        .agreement-text {
            text-align: justify;
            margin: 15px 0;
            line-height: 1.5;
        }

        /* Clause Title */
        .clause-heading {
            text-align: center;
            font-weight: bold;
            font-size: 11.5pt;
            margin: 22px 0 18px 0;
            text-transform: uppercase;
        }

        /* Numbered Clauses */
        .clause {
            margin: 12px 0;
            text-align: justify;
        }

        .clause-num {
            font-weight: bold;
            margin-right: 10px;
        }

        /* Sub-clauses */
        .sub-clause-list {
            margin: 10px 0 10px 30px;
        }

        .sub-clause {
            margin: 10px 0;
            text-align: justify;
        }

        .sub-letter {
            font-weight: bold;
            margin-right: 10px;
        }

        /* Schedule */
        .schedule-heading {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin: 30px 0 20px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .schedule-item {
            margin: 10px 0 10px 25px;
        }

        /* Signatures */
        .signature-area {
            margin-top: 50px;
        }

        .signature-block {
            margin: 35px 0;
        }

        .sig-line {
            border-top: 1.5px solid #000;
            width: 250px;
            margin-top: 55px;
            display: inline-block;
        }

        .sig-label {
            font-weight: bold;
            margin-top: 8px;
        }

        .sig-detail {
            font-size: 10pt;
            margin-top: 5px;
        }

        /* Page Break */
        .page-break {
            page-break-after: always;
        }

        strong {
            font-weight: 700;
        }

        sup {
            font-size: 7.5pt;
            vertical-align: super;
        }

        /* Watermark for sample */
        .sample-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72pt;
            color: rgba(200, 200, 200, 0.15);
            font-weight: bold;
            z-index: -1;
            pointer-events: none;
        }
    </style>
</head>
<body>
    {{-- PAGE 1 --}}
    <div class="document-header">
        <div class="header-left">
            <div class="logo-section">
                {{-- Company Logo SVG --}}
                <svg width="120" height="70" viewBox="0 0 120 70" xmlns="http://www.w3.org/2000/svg">
                    {{-- House Icon --}}
                    <g transform="translate(40, 10)">
                        <path d="M20,10 L35,1 L50,10 L50,35 L20,35 Z" fill="#2C3E50" stroke="#000" stroke-width="1.5"/>
                        <rect x="26" y="18" width="8" height="10" fill="#F7941D"/>
                        <rect x="36" y="18" width="8" height="10" fill="#34495E"/>
                        <rect x="28" y="28" width="14" height="7" fill="#34495E"/>
                        <polygon points="20,10 35,1 50,10 48,12 35,4 22,12" fill="#F7941D"/>
                    </g>
                    {{-- Text --}}
                    <text x="10" y="52" font-family="Arial" font-size="11" font-weight="bold" fill="#000">CHABRIN</text>
                    <text x="10" y="62" font-family="Arial" font-size="10" font-weight="bold" fill="#000">AGENCIES</text>
                    <text x="10" y="69" font-family="Arial" font-size="7" fill="#000">LTD</text>
                </svg>
                <div style="font-size: 7pt; margin-top: 2px;">Registered Property Management & Consultants</div>
            </div>
        </div>
        <div class="header-right">
            <div class="contact-info">
                <div class="contact-line contact-bold">NACICO PLAZA, LANDHIES ROAD</div>
                <div class="contact-line contact-bold">5<sup>TH</sup> FLOOR – ROOM 517</div>
                <div class="contact-line">P.O. Box 16659 – 00620</div>
                <div class="contact-line">NAIROBI</div>
                <div class="contact-line">CELL : +254-720-854-389</div>
                <div class="contact-line">MAIL: info@chabrinagencies.co.ke</div>
            </div>
        </div>
    </div>

    <div class="header-separator"></div>

    <div class="document-title">TENANCY LEASE AGREEMENT</div>

    <div class="section-header">BETWEEN</div>

    <div class="party-block">
        <span class="party-number">1.</span>
        <span class="field-fill">{{ $landlord->name ?? '___________________________________' }}</span> c/o
        <div class="party-detail">
            <strong>MANAGING AGENT: CHABRIN AGENCIES LTD</strong><br>
            <strong>P O BOX 16659-00620</strong><br>
            <strong>NAIROBI</strong>
        </div>
    </div>

    <div class="section-header">AND</div>

    <div class="party-block">
        <span class="party-number">2.</span>
        <span class="field-label">TENANT:</span>
        <span class="field-fill" style="min-width: 350px;">{{ $tenant->full_name }}</span>
    </div>

    <div class="form-row">
        <span class="field-label">ID NO:</span>
        <span class="field-fill">{{ $tenant->id_number }}</span>
        <span class="field-label">(Attach copy) Tel:</span>
        <span class="field-fill">{{ $tenant->phone }}</span>
    </div>

    <div class="form-row">
        <span class="field-label">ADDRESS:</span>
        <span class="field-fill" style="min-width: 400px;">{{ $tenant->address ?? 'N/A' }}</span>
    </div>

    <div class="form-row">
        <span class="field-label">NEXT OF KIN:</span>
        <span class="field-fill">{{ $tenant->next_of_kin_name ?? 'N/A' }}</span>
        <span class="field-label">Tel:</span>
        <span class="field-fill">{{ $tenant->next_of_kin_phone ?? 'N/A' }}</span>
    </div>

    <div class="premises-title">IN RESPECT OF RESIDENTIAL PREMISES DESIGNED AS:</div>

    <div class="premises-fields">
        <span class="field-label">PLOT NO:</span>
        <span class="field-fill">{{ $property->plot_number ?? 'N/A' }}</span>
        <span class="field-label" style="margin-left: 30px;">Flat no:</span>
        <span class="field-fill">{{ $unit->unit_number }}</span>
    </div>

    <div class="agreement-text">
        This tenancy agreement is made on the
        <span class="field-fill" style="min-width: 150px;">{{ $lease->start_date->format('d / m / Y') }}</span>
        between <strong>{{ $landlord->name ?? '____________________________' }}</strong> c/o CHABRIN AGENCIES LTD of Post Office number
        16659-00620 Nairobi In the Republic of Kenya (herein called "the managing agent" which
        expression shall where the context so admits include its successors and assigns) of the
        one part and <strong>{{ $tenant->full_name }}</strong> of ID No <strong>{{ $tenant->id_number }}</strong> Post Office
        number <strong>{{ $tenant->postal_address ?? 'N/A' }}</strong> (Hereafter called "the tenant" which expression shall where
        the context so admits include his/her personal representatives and assigns) of the other
        part.
    </div>

    {{-- SUBSEQUENT PAGES - Continue with clauses as before but with proper formatting --}}

    <div class="clause-heading" style="margin-top: 30px;">NOW THIS TENANCY AGREEMENT WITNESSES AS FOLLOWS:</div>

    <div class="clause">
        <span class="clause-num">1.</span>
        That landlord hereby grants and the tenant hereby accepts a lease of the premises
        (hereinafter called the "premises") described in the schedule hereto for the term of
        and at the rent specified in the said schedule, payable as provided in the said
        schedule subject to the covenants agreements conditions, stipulations and provisions
        contained hereinafter.
    </div>

    <div class="clause">
        <span class="clause-num">2.</span>
        The tenants covenants with the landlord as follows:-
    </div>

    <div class="sub-clause-list">
        <div class="sub-clause">
            <span class="sub-letter">a.</span>
            To pay the rent as stated in the schedule without any deductions whatsoever
            to the landlord or the landlord's duly appointed agents.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">b.</span>
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
            <span class="sub-letter">c.</span>
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
            <span class="sub-letter">d.</span>
            To pay all electricity and water conservancy charges in respect of the said
            premises throughout the terms hereby created or to the date of its sooner
            termination as hereinafter provided.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">e.</span>
            To keep the interior of the said premises including all doors, windows, locks,
            fasteners, keys, water taps and all internal sanitary apparatus and electric light
            fittings in good and tenantable repair and proper working order and condition
            (fair wear and tear expected).
        </div>

        <div class="sub-clause">
            <span class="sub-letter">f.</span>
            Not to make alterations in or additions to the said premise without the
            landlord's prior consent in writing.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">g.</span>
            Not without the landlord's prior consent in writing to alter or interfere with the
            plumbing or electrical installations other than to keep in repair and to replace
            as and when necessary all switches fuses and elements forming part of the
            electrical installations.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">h.</span>
            To replace and be responsible for the cost of any keys which are damaged or
            lost and their appropriate interior and exterior doors and locks.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">i.</span>
            To permit the landlord or the landlord's agent to enter and view the condition
            of the said premises and upon notice given by the landlord forthwith to repair
            in accordance with such notice and in the event of the tenant not carrying
            out such repairs within fourteen days of the said notice the cost shall be a debt
            due from the landlord and shall be forthwith recoverable by action as rent.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">j.</span>
            To use the premises as a residential premises for the tenant only.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">k.</span>
            Not to permit any sale by auction to be held upon the said premises.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">l.</span>
            Not to suffer any part of the said premises to be used as to cause annoyance
            or inconvenience to the occupiers of the adjacent or neighboring flat or
            premises.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">m.</span>
            Not to suffer any part of the said premises to be used for any illegal purpose.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">n.</span>
            Not to assign underlet or part with possession of any part of the said premises
            without the prior consent in writing or the landlord, first had and obtained.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">o.</span>
            During the last one (1) months of the term hereby created to permit the
            landlord to affix upon the said premises a notice for re-letting and to permit
            persons with authority from the landlord or the landlord's agent or agents at
            reasonable times to view the said premises by prior appointment.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">p.</span>
            To yield up the said premises with all fixtures (other than the tenant's fixtures)
            and additions at the expiration or sooner determination of the tenancy in good
            and tenantable repair and condition and good as the tenant found them at
            the commencement of the lease.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">q.</span>
            In case of breach of this tenancy agreement the tenant or the landlord is
            entitled to one month's notice in writing or paying one month rent in lieu
            thereof to terminate the term hereby created.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">r.</span>
            To pay service charge e.g. security and garbage collection. The responsibility
            to appoint agents of these services rest on tenants unless where the landlord is
            requested to assist.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">s.</span>
            All payments are strictly made to our accounts as provided. Personal cheques
            are not acceptable. Any cheque returned to us unpaid will attract an
            immediate penalty of Kshs 3,500.
        </div>
    </div>

    <div class="clause" style="margin-top: 20px;">
        <span class="clause-num">3.</span>
        The landlord covenant with the tenant as follows:
    </div>

    <div class="sub-clause-list">
        <div class="sub-clause">
            <span class="sub-letter">a.</span>
            To permit the tenant to peacefully hold and enjoy the said premises during the
            said term without any interruption by the landlord or any person or agents
            rightfully claiming under or in trust of the landlord, so long as the tenant pays
            the rent hereby reserved and performs and observes the several covenants
            and the conditions herein contained.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">b.</span>
            To keep the walls, roof and structure of the premises in good and tenantable
            state of repair and maintenance.
        </div>

        <div class="sub-clause">
            <span class="sub-letter">c.</span>
            To keep adequately lighted, cleaned and in good state the repair and
            condition the entrance halls and all common area of the said premises.
        </div>
    </div>

    <div class="clause">
        <span class="clause-num">4.</span>
        The landlord shall have a right of re-entry and possession if any rent shall not have
        been paid as agreed or on breach or non-observance by the tenant of any covenant
        herein contained or on bankruptcy or composition with creditors or suffering distress
        or execution. In that event this agreement shall stand terminated automatically,
        without prejudice to landlord's rights under this agreement.
    </div>

    <div class="clause">
        <span class="clause-num">5.</span>
        In case of the premises being required for statutory duties or re-construction the
        landlord shall give the tenant notice not more than six months from the date of
        service.
    </div>

    <div class="clause">
        <span class="clause-num">6.</span>
        Any party hereto wishing to terminate the tenancy created hereby shall serve upon
        the other party written notice of his/her intention to do so and such notice shall be for
        a period of one (1) calendar month. Rent will be payable to and inclusive of the date
        stipulated in the notice.
    </div>

    <div class="clause">
        <span class="clause-num">7.</span>
        The landlord shall not be liable for loss or damage to any property of the tenant in
        the said premises from any cause whatsoever.
    </div>

    <div class="schedule-heading" style="margin-top: 40px;">THE SCHEDULE ABOVE REFERRED TO</div>

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
        <strong>THE RENT:</strong> Kenya Shillings {{ number_format($lease->monthly_rent, 2) }} per month payable in advance on or before the 5<sup>th</sup> day of each calendar month
    </div>

    <div class="schedule-item">
        <strong>SECURITY DEPOSIT:</strong> Kenya Shillings {{ number_format($lease->deposit_amount, 2) }}
    </div>

    <div class="signature-area">
        <div class="signature-block">
            <div style="font-weight: bold;">SIGNED by the LANDLORD/MANAGING AGENT:</div>
            <div class="sig-line"></div>
            <div class="sig-label">{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</div>
            <div class="sig-detail">Date: ___________________</div>
        </div>

        <div class="signature-block">
            <div style="font-weight: bold;">SIGNED by the TENANT:</div>
            <div class="sig-line"></div>
            <div class="sig-label">{{ $tenant->full_name }}</div>
            <div class="sig-detail">ID Number: {{ $tenant->id_number }}</div>
            <div class="sig-detail">Date: ___________________</div>
        </div>
    </div>
</body>
</html>
