<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 35px 45px;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #000;
        }

        .cover-page {
            text-align: center;
            padding-top: 100px;
            page-break-after: always;
        }

        .cover-title {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        .cover-subtitle {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .section-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 25px 0 15px 0;
            text-transform: uppercase;
        }

        .section-number {
            font-size: 12pt;
            font-weight: bold;
            margin: 20px 0 10px 0;
        }

        .particulars-table {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
        }

        .particulars-row {
            margin: 12px 0;
        }

        .particular-label {
            font-weight: bold;
            display: inline-block;
            min-width: 150px;
            vertical-align: top;
        }

        .particular-value {
            display: inline;
        }

        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
            padding: 0 5px;
        }

        .clause-content {
            text-align: justify;
            margin: 10px 0 10px 30px;
        }

        .sub-clause {
            margin: 8px 0 8px 50px;
            text-align: justify;
        }

        .sub-clause-letter {
            font-weight: bold;
            margin-right: 10px;
            display: inline-block;
            min-width: 20px;
        }

        .nested-clause {
            margin: 6px 0 6px 70px;
            text-align: justify;
        }

        .nested-number {
            font-weight: bold;
            margin-right: 8px;
            display: inline-block;
            min-width: 25px;
        }

        .highlight-box {
            background: #f0f0f0;
            padding: 12px;
            margin: 15px 0;
            border-left: 4px solid #333;
        }

        .signature-page {
            page-break-before: always;
            margin-top: 30px;
        }

        .signature-block {
            margin: 40px 0;
            page-break-inside: avoid;
        }

        .signature-line {
            border-top: 1.5px solid #000;
            width: 250px;
            margin-top: 50px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
        }

        .signature-name {
            font-weight: bold;
            margin-top: 8px;
        }

        .signature-details {
            font-size: 9pt;
            margin-top: 3px;
            color: #333;
        }

        .page-header {
            text-align: right;
            font-size: 8pt;
            margin-bottom: 15px;
            color: #666;
        }

        .page-footer {
            text-align: center;
            font-size: 8pt;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .qr-code-section {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .qr-code-section img {
            width: 90px;
            height: 90px;
        }

        table.details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table.details-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        table.details-table td.label-cell {
            font-weight: bold;
            width: 35%;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    {{-- Cover Page --}}
    <div class="cover-page">
        <div class="cover-title">COMMERCIAL</div>
        <div class="cover-title">LEASE</div>
        <div class="cover-subtitle">AGREEMENT</div>

        <div style="margin-top: 80px; font-size: 12pt;">
            <strong>Reference Number:</strong><br>
            <span style="font-size: 16pt; font-weight: bold;">{{ $lease->reference_number }}</span>
        </div>

        <div style="margin-top: 40px; font-size: 11pt;">
            <strong>Date of Agreement:</strong><br>
            {{ $lease->start_date->format('jS \d\a\y \o\f F, Y') }}
        </div>

        @if(isset($qrCode) && $qrCode)
        <div style="margin-top: 60px;">
            <img src="{{ $qrCode }}" alt="Lease QR Code" style="width: 120px; height: 120px;">
            <div style="font-size: 9pt; margin-top: 10px;">Scan for lease verification</div>
        </div>
        @endif
    </div>

    {{-- Main Content --}}
    <div class="page-header">
        Commercial Lease Agreement | Ref: {{ $lease->reference_number }}
    </div>

    {{-- Section 1: Particulars --}}
    <div class="section-title">1. PARTICULARS</div>

    <div class="particulars-row">
        <span class="particular-label">Date:</span>
        <span class="particular-value">
            This Lease Agreement is dated the <strong>{{ $lease->start_date->format('jS') }}</strong> day of the month of
            <strong>{{ $lease->start_date->format('F') }}</strong>, in the year <strong>{{ $lease->start_date->format('Y') }}</strong>.
        </span>
    </div>

    <div class="particulars-row" style="margin-top: 20px;">
        <span class="particular-label">The Lessor:</span>
        <span class="particular-value">
            <strong>{{ $landlord->name ?? '____________________________' }}</strong> of Post Office Box Number
            <strong>{{ $landlord->postal_address ?? '____________________________' }}</strong> and where the
            context so admits includes its successors in title and assigns; of the other part.
        </span>
    </div>

    <div class="particulars-row" style="margin-top: 20px;">
        <span class="particular-label">The Lessee:</span>
        <span class="particular-value">
            <strong>{{ $tenant->full_name }}</strong> of ID.No or Company registration no.
            <strong>{{ $tenant->id_number }}</strong> and of Post Office Box Number
            <strong>{{ $tenant->postal_address ?? 'N/A' }}</strong> Nairobi, and where the
            context so admits includes its successors in title and assigns; of the other part.
        </span>
    </div>

    <div class="particulars-row" style="margin-top: 20px;">
        <span class="particular-label">The Building:</span>
        <span class="particular-value">
            The building and improvement on the parcel identified as <strong>{{ $property->name ?? 'N/A' }}</strong>
            constructed on all that piece of L.R. <strong>{{ $property->lr_number ?? 'N/A' }}</strong>
            Designed as <strong>{{ $unit->unit_number }}</strong>.
        </span>
    </div>

    {{-- Lease Terms Table --}}
    <table class="details-table" style="margin-top: 25px;">
        <tr>
            <td class="label-cell">The Term:</td>
            <td>
                <strong>{{ $lease->duration_months }} months</strong> from
                <strong>{{ $lease->start_date->format('d/m/Y') }}</strong> to
                <strong>{{ $lease->end_date->format('d/m/Y') }}</strong>
            </td>
        </tr>
        <tr>
            <td class="label-cell">The Base Rent:</td>
            <td><strong>KES {{ number_format($lease->monthly_rent, 2) }}</strong> per month</td>
        </tr>
        <tr>
            <td class="label-cell">Deposit:</td>
            <td>
                KES <strong>{{ number_format($lease->deposit_amount, 2) }}</strong>, to be paid as security bond refundable
                after giving vacant possession and the same shall not attract any interest.
            </td>
        </tr>
        <tr>
            <td class="label-cell">Other Charges:</td>
            <td>Security and any other charges payable by the Lessee either statutory or to the County Government.</td>
        </tr>
        <tr>
            <td class="label-cell">Value Added Tax:</td>
            <td>
                The rent shall be subjected to Value Added Tax (V.A.T) at a statutory rate of 16%, which translates to
                KES <strong>{{ number_format($lease->monthly_rent * 0.16, 2) }}</strong> to be paid over and above the base rent.
            </td>
        </tr>
        <tr>
            <td class="label-cell">Rent In Advance:</td>
            <td>
                The rent shall be paid in advance on or before the <strong>1<sup>st</sup></strong> day of
                every month deadline by <strong>5<sup>th</sup></strong> (fifth) of the month due.
            </td>
        </tr>
        <tr>
            <td class="label-cell">Rent Review:</td>
            <td>
                Shall be reviewed after each <strong>{{ $lease->rent_review_period ?? '2' }}</strong> year(s) at a guide rate
                of <strong>{{ $lease->rent_review_percentage ?? '10' }}%</strong>. The review shall be communicated in writing
                and in advance offering a period of 3 months' notice.
            </td>
        </tr>
        <tr>
            <td class="label-cell">Payment:</td>
            <td>All the payments will be done to <strong>Chabrin Agencies Limited</strong></td>
        </tr>
    </table>

    {{-- Section 2: Grant of Lease --}}
    <div style="page-break-before: always;"></div>

    <div class="page-header">
        Commercial Lease Agreement | Ref: {{ $lease->reference_number }} | Page 2
    </div>

    <div class="section-number">2. GRANT OF LEASE</div>

    <div class="clause-content">
        The Lessor leases to the Lessee for a period of <strong>{{ $lease->duration_months }} months</strong> from the date of this
        Agreement all rights, easements, privileges, restrictions, covenants and stipulations of
        whatever nature affecting the Premises and subject to the payment to the Lessor of:
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">a)</span>
        The rent, which shall be paid on a monthly basis, that is, in advance.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">b)</span>
        Rent shall be payable on or before the fifth (5<sup>th</sup>) day of the month when the rent shall be due.
    </div>

    {{-- Section 3: Lessee's Covenants --}}
    <div class="section-number">3. THE LESSEE'S COVENANTS:</div>

    <div class="clause-content">
        The Lessee covenants with the Lessor:
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">a)</span>
        To pay the rents on the days prescribed and in the manner set out in this lease, not to
        exercise any right or claim to withhold rent or any right or claim to legal or equitable set
        off and if so required by the Lessor, to make such payments to the bank and account
        which the Lessor may from time to time nominate.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">b)</span>
        To pay to the suppliers and to indemnify the Lessor against all charges for electricity,
        water and other services consumed at or in relation to the allocated Premises.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">c)</span>
        To keep the Premises in clean and habitable condition.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">d)</span>
        Not to commit waste nor make any addition or alteration to the Premises without prior
        written consent of the Lessor. The Lessee may install internal demountable partitions
        which shall be approved by the Lessor and removed at the expiration of the Term if
        required by the Lessor and any damage to the Premises caused by the removal made good.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">e)</span>
        Not to affix to nor exhibit on the outside of the premises or to any window of the
        premises or anywhere in the Common parts any name-plate, sign, notice or
        advertisement except with approval from the Lessor.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">f)</span>
        To use the Premises for business and commercial purposes only as shall be approved by
        the Lessor and not to use the Premises for any illegal or immoral purpose.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">g)</span>
        Not to do or permit to be done on the Premises anything which may be or become a
        nuisance, annoyance, or disturbance to the Lessor or to other tenants or occupiers of
        the Building or neighbouring premises.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">h)</span>
        Not to assign, sublet or part with possession of the whole or any part of the Premises
        without the prior written consent of the Lessor (which consent shall not be unreasonably
        withheld or delayed).
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">i)</span>
        To permit the Lessor and its agents at all reasonable times on reasonable notice to
        enter and inspect the Premises and to execute any repairs or works which the Lessor
        may be required to carry out.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">j)</span>
        To comply with all statutes, regulations, and by-laws relating to the Premises and the
        use and occupation thereof and to obtain and maintain all necessary licenses and
        permits required for the business conducted on the Premises.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">k)</span>
        To insure and keep insured all contents and fixtures installed by the Lessee in the
        Premises against loss or damage by fire and other risks as the Lessor may reasonably require.
    </div>

    {{-- Section 4: Termination --}}
    <div style="page-break-before: always;"></div>

    <div class="page-header">
        Commercial Lease Agreement | Ref: {{ $lease->reference_number }} | Page 3
    </div>

    <div class="section-number">4. TERMINATION</div>

    <div class="clause-content">
        This lease may be terminated:
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">a)</span>
        By either party giving to the other not less than three (3) calendar months' notice in
        writing to expire on any rent payment date.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">b)</span>
        By the Lessor if the rent or any part thereof is in arrears for thirty (30) days after
        becoming due whether formally demanded or not.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">c)</span>
        By the Lessor if the Lessee is in breach of any covenant or condition contained in this
        lease and fails to remedy such breach within fourteen (14) days of written notice from
        the Lessor requiring the Lessee to do so.
    </div>

    {{-- Section 5: Lessor's Covenants --}}
    <div class="section-number">5. THE LESSOR'S COVENANTS:</div>

    <div class="clause-content">
        The Lessor covenants with the Lessee:
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">a)</span>
        To allow the Lessee quiet enjoyment of the Premises without any interruption by the
        Lessor or any person lawfully claiming under or in trust for the Lessor.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">b)</span>
        To keep in good and substantial repair the structure and exterior of the Building
        including the roof, foundations, external walls and windows.
    </div>

    <div class="sub-clause">
        <span class="sub-clause-letter">c)</span>
        To maintain public liability insurance for the Building and the common parts.
    </div>

    {{-- Important Notice Box --}}
    <div class="highlight-box" style="margin-top: 30px;">
        <strong>IMPORTANT NOTICE TO LESSEE:</strong><br><br>
        This is a legally binding commercial lease agreement. You are advised to read all terms
        carefully and seek legal advice if necessary before signing. By signing this agreement,
        you acknowledge that you have read, understood, and agree to be bound by all terms
        and conditions contained herein.
    </div>

    {{-- Signature Page --}}
    <div class="signature-page">
        <div class="page-header">
            Commercial Lease Agreement | Ref: {{ $lease->reference_number }} | Signature Page
        </div>

        <div style="text-align: center; font-size: 13pt; font-weight: bold; margin-bottom: 40px; text-transform: uppercase;">
            EXECUTION
        </div>

        <div style="margin-bottom: 30px; text-align: center;">
            IN WITNESS WHEREOF the parties hereto have executed this Agreement on the date first above written.
        </div>

        {{-- Lessor Signature --}}
        <div class="signature-block">
            <div class="signature-title">SIGNED by the LESSOR/MANAGING AGENT:</div>

            <div class="signature-line"></div>

            <div class="signature-name">{{ $landlord->name ?? 'CHABRIN AGENCIES LIMITED' }}</div>
            <div class="signature-details">Managing Agent</div>
            <div class="signature-details">P.O. Box 16659-00620, Nairobi</div>
            <div class="signature-details">Date: _______________________</div>

            @if($landlord)
            <div style="margin-top: 15px;">
                <div class="signature-details">Name: {{ $landlord->contact_person ?? '____________________________' }}</div>
                <div class="signature-details">Designation: {{ $landlord->contact_designation ?? '____________________________' }}</div>
            </div>
            @endif
        </div>

        {{-- Lessee Signature --}}
        <div class="signature-block">
            <div class="signature-title">SIGNED by the LESSEE:</div>

            <div class="signature-line"></div>

            <div class="signature-name">{{ $tenant->full_name }}</div>
            <div class="signature-details">ID Number: {{ $tenant->id_number }}</div>
            <div class="signature-details">P.O. Box: {{ $tenant->postal_address ?? 'N/A' }}</div>
            <div class="signature-details">Tel: {{ $tenant->phone }}</div>
            <div class="signature-details">Date: _______________________</div>
        </div>

        {{-- Witness Section --}}
        <div style="margin-top: 50px; page-break-inside: avoid;">
            <div style="font-weight: bold; margin-bottom: 20px;">WITNESSED BY:</div>

            <div style="margin: 30px 0;">
                <div class="signature-line" style="width: 220px;"></div>
                <div style="font-weight: bold; margin-top: 5px;">Witness Name: ____________________________</div>
                <div style="font-size: 9pt; margin-top: 3px;">ID Number: ____________________________</div>
                <div style="font-size: 9pt; margin-top: 3px;">Date: ____________________________</div>
            </div>
        </div>
    </div>

    {{-- Final Footer --}}
    <div class="page-footer">
        <div style="font-weight: bold;">COMMERCIAL LEASE AGREEMENT</div>
        <div style="margin-top: 5px;">Reference: {{ $lease->reference_number }}</div>
        <div style="margin-top: 5px;">Generated: {{ now()->format('d/m/Y H:i:s') }}</div>
        <div style="margin-top: 10px; font-size: 7pt;">
            Chabrin Agencies Ltd | P.O. Box 16659-00620, Nairobi | Tel: +254-720-854-389 | Email: info@chabrinagencies.co.ke
        </div>
    </div>
</body>
</html>
