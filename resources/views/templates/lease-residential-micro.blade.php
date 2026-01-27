<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenancy Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 25px 35px;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }

        .header .company-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header .address-line {
            font-size: 9pt;
            margin: 2px 0;
        }

        .document-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .agreement-intro {
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #333;
        }

        .intro-line {
            margin: 5px 0;
        }

        .field-label {
            font-weight: bold;
            display: inline-block;
            min-width: 140px;
        }

        .field-value {
            display: inline;
            border-bottom: 1px dotted #666;
            padding: 0 3px;
        }

        .property-details {
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #ddd;
            background: #fafafa;
        }

        .conditions-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 25px 0 15px 0;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }

        .condition-item {
            margin: 12px 0;
            padding-left: 10px;
            text-align: justify;
        }

        .condition-number {
            font-weight: bold;
            margin-right: 8px;
            display: inline-block;
            min-width: 25px;
        }

        .highlight-box {
            background: #fffacd;
            padding: 10px;
            margin: 15px 0;
            border: 1px solid #f0e68c;
            border-radius: 3px;
        }

        .important-note {
            color: #d32f2f;
            font-weight: bold;
        }

        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-row {
            display: table;
            width: 100%;
            margin: 30px 0;
        }

        .signature-cell {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-top: 40px;
        }

        .signature-label {
            font-weight: bold;
            margin-top: 5px;
            font-size: 10pt;
        }

        .signature-details {
            font-size: 9pt;
            margin-top: 5px;
            color: #555;
        }

        .footer {
            text-align: center;
            font-size: 8pt;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            color: #666;
        }

        .qr-section {
            float: right;
            margin: 10px;
        }

        .qr-section img {
            width: 80px;
            height: 80px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="company-name">NACICO PLAZA, LANDHIES ROAD</div>
        <div class="address-line">5<sup>TH</sup> FLOOR – ROOM 517</div>
        <div class="address-line">P.O. Box 16659 – 00620, NAIROBI</div>
        <div class="address-line">CELL: +254-720-854-389 | MAIL: info@chabrinagencies.co.ke</div>
    </div>

    {{-- QR Code --}}
    @if(isset($qrCode) && $qrCode)
    <div class="qr-section">
        <img src="{{ $qrCode }}" alt="QR Code">
        <div style="font-size: 7pt; text-align: center;">{{ $lease->reference_number }}</div>
    </div>
    @endif

    {{-- Document Title --}}
    <div class="document-title">TENANCY AGREEMENT</div>

    {{-- Agreement Introduction --}}
    <div class="agreement-intro">
        <div class="intro-line">
            <strong>THIS AGREEMENT</strong> is made this <strong>{{ $lease->start_date->format('jS') }}</strong> day of
            <strong>{{ $lease->start_date->format('F') }}</strong> <strong>{{ $lease->start_date->format('Y') }}</strong> between
            <strong>{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</strong> "The duly appointed Managing Agent" of the said property and:
        </div>
    </div>

    {{-- Tenant Details --}}
    <div style="margin: 20px 0;">
        <div style="margin: 8px 0;">
            <span class="field-label">TENANT'S NAME:</span>
            <span class="field-value">{{ $tenant->full_name }}</span>
        </div>

        <div style="margin: 8px 0;">
            <span class="field-label">ID:</span>
            <span class="field-value">{{ $tenant->id_number }}</span>
            <span class="field-label" style="margin-left: 30px; min-width: 100px;">ADDRESS:</span>
            <span class="field-value">{{ $tenant->address ?? 'N/A' }}</span>
        </div>

        <div style="margin: 8px 0;">
            <span class="field-label">TEL:</span>
            <span class="field-value">{{ $tenant->phone }}</span>
            <span class="field-label" style="margin-left: 30px; min-width: 130px;">PLACE OF WORK:</span>
            <span class="field-value">{{ $tenant->workplace ?? 'N/A' }}</span>
        </div>

        <div style="margin: 8px 0;">
            <span class="field-label">NEXT OF KIN:</span>
            <span class="field-value">{{ $tenant->next_of_kin_name ?? 'N/A' }}</span>
            <span class="field-label" style="margin-left: 30px; min-width: 60px;">TEL:</span>
            <span class="field-value">{{ $tenant->next_of_kin_phone ?? 'N/A' }}</span>
        </div>
    </div>

    {{-- Property Details --}}
    <div class="property-details">
        <div style="margin: 5px 0;">
            <span class="field-label">PROPERTY NAME:</span>
            <span class="field-value">{{ $property->name ?? 'N/A' }}</span>
            <span class="field-label" style="margin-left: 30px; min-width: 100px;">ROOM NO:</span>
            <span class="field-value">{{ $unit->unit_number }}</span>
        </div>

        <div style="margin: 5px 0;">
            <span class="field-label">HOUSE DEPOSIT PAID:</span>
            <span class="field-value">KES {{ number_format($lease->deposit_amount, 2) }}</span>
            <span class="field-label" style="margin-left: 30px; min-width: 110px;">RECEIPT NO:</span>
            <span class="field-value">{{ $lease->reference_number }}</span>
        </div>

        <div style="margin: 5px 0;">
            <span class="field-label">MONTHLY RENT:</span>
            <span class="field-value" style="font-weight: bold; font-size: 12pt;">KES {{ number_format($lease->monthly_rent, 2) }}</span>
            <span class="field-label" style="margin-left: 30px; min-width: 60px;">DATE:</span>
            <span class="field-value">{{ $lease->start_date->format('d/m/Y') }}</span>
        </div>
    </div>

    {{-- Agreement Clause --}}
    <div style="margin: 20px 0; text-align: center; font-weight: bold;">
        WHERE IT IS AGREED BETWEEN the parties as follows:-
    </div>

    {{-- Conditions --}}
    <div class="conditions-title">CONDITIONS</div>

    <div class="condition-item">
        <span class="condition-number">1.</span>
        Rent is <span class="important-note">STRICTLY</span> payable on or before the <strong>1<sup>st</sup></strong> day of the month and the deadline will
        be on the <strong>5<sup>th</sup></strong> of every month during the tenancy period.
    </div>

    <div class="condition-item">
        <span class="condition-number">2.</span>
        An equivalent of one-month rent will be paid as deposit and a <strong>KES {{ number_format($lease->water_deposit ?? 1000, 2) }}</strong>
        electricity and water deposits payable to Chabrin Agencies Ltd bank accounts. The
        rent deposit sum is refundable at the termination of this tenancy with proper one (1)
        calendar months' written notice. The said sum may be utilized to defray any
        outstanding conservancy charges, damages or expenses which would be at all
        material times may be payable by the tenant within the tenancy period and such,
        the deposit should <span class="important-note">NEVER</span> be used as the last months' rent payment. Refunds done
        on <strong>25<sup>th</sup>/26<sup>th</sup></strong> of the month upon following the laid down procedures.
    </div>

    <div class="condition-item">
        <span class="condition-number">3.</span>
        Either party can terminate this agreement by giving a one (1) calendar Months'
        notice in writing.
    </div>

    <div class="condition-item">
        <span class="condition-number">4.</span>
        The property owner will only allow established occupants before renting out a unit
        in the premise.
    </div>

    <div class="condition-item">
        <span class="condition-number">5.</span>
        To permit the Landlord, his agents, workmen or servants at all reasonable times on
        notice from the landlord whether oral or written to enter upon the said premises or
        part thereof and execute structural or other repairs to the building.
    </div>

    <div class="condition-item">
        <span class="condition-number">6.</span>
        No reckless use of water will be tolerated. Only authorized occupants will enjoy this
        facility.
    </div>

    <div class="condition-item">
        <span class="condition-number">7.</span>
        Anti-social activities likely to inconvenience other tenants like loud music or any other
        unnecessary noise <span class="important-note">SHALL NOT</span> be tolerated and as such, the said behavior shall be
        deemed as breach of this agreement that shall form the basis of terminating the
        tenancy without further reference to the tenant.
    </div>

    <div class="condition-item">
        <span class="condition-number">8.</span>
        It will be the responsibility of every tenant to keep the premises clean.
    </div>

    <div class="condition-item">
        <span class="condition-number">9.</span>
        Sources of energy such as firewood, charcoal, open lamps or any other smoking
        instrument should not be used in the premises.
    </div>

    <div class="condition-item">
        <span class="condition-number">10.</span>
        To use the premises for private residential purposes only and not carry any form of
        business or use them as a boarding house or any other unauthorized purpose without
        the consent of the Landlord in writing.
    </div>

    <div class="condition-item">
        <span class="condition-number">11.</span>
        Not to make or permit to made any alterations in or additions to the said premises nor
        to erect any fixtures therein nor drive any nails, screws, bolts or wedges in the floors,
        walls or ceilings thereof without the consent in writing of the Landlord first hand and
        obtained (which consent shall not unreasonably withheld).
    </div>

    <div class="condition-item">
        <span class="condition-number">12.</span>
        Not to sublet or let out the space apportioned under the lease. Breach of this clause
        will lead to immediate termination of the running lease.
    </div>

    <div class="condition-item">
        <span class="condition-number">13.</span>
        Deposit should be updated from time to time as the house rent is adjusted.
    </div>

    <div class="condition-item">
        <span class="condition-number">14.</span>
        The tenant will be required to pay for any damages to the property during the tenancy
        period.
    </div>

    <div class="condition-item">
        <span class="condition-number">15.</span>
        The landlord reserves the right to increase rent after giving three (3) months' notice
        in writing.
    </div>

    {{-- Important Notice --}}
    <div class="highlight-box">
        <strong>IMPORTANT NOTICE:</strong> This agreement shall commence on
        <strong>{{ $lease->start_date->format('d/m/Y') }}</strong> and continue on a month-to-month basis
        until terminated by either party as per the terms stated herein.
    </div>

    {{-- Signature Section --}}
    <div class="signature-section">
        <div class="signature-row">
            <div class="signature-cell">
                <div><strong>LANDLORD / AGENT</strong></div>
                <div class="signature-line"></div>
                <div class="signature-label">{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</div>
                <div class="signature-details">Managing Agent</div>
                <div class="signature-details">Date: _______________</div>
            </div>

            <div class="signature-cell">
                <div><strong>TENANT</strong></div>
                <div class="signature-line"></div>
                <div class="signature-label">{{ $tenant->full_name }}</div>
                <div class="signature-details">ID: {{ $tenant->id_number }}</div>
                <div class="signature-details">Date: _______________</div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div>This is a legally binding document. Both parties should retain a copy for their records.</div>
        <div style="margin-top: 5px;">Lease Reference: <strong>{{ $lease->reference_number }}</strong></div>
        <div style="margin-top: 5px;">Generated on: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
