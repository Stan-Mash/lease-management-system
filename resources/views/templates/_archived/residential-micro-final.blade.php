<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenancy Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 0.5in 0.75in;
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
            line-height: 1.45;
            color: #000;
        }

        /* Header with Logo and Contact */
        .doc-header {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .logo-left {
            display: table-cell;
            width: 30%;
            vertical-align: top;
        }

        .logo-svg {
            width: 115px;
            height: auto;
        }

        .contact-right {
            display: table-cell;
            width: 70%;
            vertical-align: top;
            text-align: right;
            padding-left: 15px;
        }

        .contact-text {
            font-size: 9.5pt;
            line-height: 1.25;
            color: #F7941D;
            font-weight: 600;
        }

        .contact-text div {
            margin: 2px 0;
        }

        /* Yellow separator line */
        .yellow-line {
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #F7941D 0%, #FDB913 100%);
            margin: 8px 0 18px 0;
        }

        /* Main title */
        .main-title {
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            margin: 20px 0 18px 0;
            text-transform: uppercase;
        }

        /* Agreement intro box */
        .intro-box {
            margin: 15px 0;
            line-height: 1.6;
        }

        .intro-line {
            margin: 5px 0;
        }

        /* Field styling */
        .label {
            font-weight: bold;
            display: inline;
        }

        .fill-line {
            display: inline-block;
            border-bottom: 1px dotted #333;
            min-width: 120px;
            padding: 0 3px;
        }

        /* Property box */
        .property-box {
            margin: 18px 0;
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }

        .prop-row {
            margin: 6px 0;
            font-size: 10.5pt;
        }

        /* Conditions section */
        .conditions-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 25px 0 15px 0;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }

        .condition {
            margin: 12px 0;
            text-align: justify;
        }

        .cond-num {
            font-weight: bold;
            margin-right: 8px;
            display: inline-block;
            min-width: 25px;
        }

        /* Highlight important text */
        .important {
            color: #d32f2f;
            font-weight: bold;
        }

        /* Notice box */
        .notice-box {
            background: #fffacd;
            padding: 12px;
            margin: 18px 0;
            border: 1px solid #f0e68c;
            border-radius: 3px;
            font-size: 10.5pt;
        }

        /* Signatures */
        .sig-section {
            margin-top: 45px;
        }

        .sig-row {
            display: table;
            width: 100%;
            margin: 30px 0;
        }

        .sig-cell {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
        }

        .sig-line {
            border-top: 1.5px solid #000;
            width: 200px;
            margin-top: 50px;
        }

        .sig-name {
            font-weight: bold;
            margin-top: 8px;
            font-size: 10.5pt;
        }

        .sig-info {
            font-size: 9pt;
            margin-top: 4px;
            color: #555;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 8.5pt;
            margin-top: 35px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            color: #666;
        }

        sup {
            font-size: 7.5pt;
            vertical-align: super;
        }

        strong {
            font-weight: 700;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="doc-header">
        <div class="logo-left">
            <svg class="logo-svg" viewBox="0 0 120 70" xmlns="http://www.w3.org/2000/svg">
                <g transform="translate(40, 10)">
                    <path d="M20,10 L35,1 L50,10 L50,35 L20,35 Z" fill="#2C3E50" stroke="#000" stroke-width="1.5"/>
                    <rect x="26" y="18" width="8" height="10" fill="#F7941D"/>
                    <rect x="36" y="18" width="8" height="10" fill="#34495E"/>
                    <rect x="28" y="28" width="14" height="7" fill="#34495E"/>
                    <polygon points="20,10 35,1 50,10 48,12 35,4 22,12" fill="#F7941D"/>
                </g>
                <text x="10" y="52" font-family="Arial" font-size="11" font-weight="bold">CHABRIN</text>
                <text x="10" y="62" font-family="Arial" font-size="10" font-weight="bold">AGENCIES</text>
                <text x="10" y="69" font-family="Arial" font-size="7">LTD</text>
            </svg>
            <div style="font-size: 7pt; margin-top: 1px;">Registered Property Management & Consultants</div>
        </div>
        <div class="contact-right">
            <div class="contact-text">
                <div style="font-weight: 700;">NACICO PLAZA, LANDHIES ROAD</div>
                <div style="font-weight: 700;">5<sup>TH</sup> FLOOR – ROOM 517</div>
                <div>P.O. Box 16659 – 00620</div>
                <div>NAIROBI</div>
                <div>CELL : +254-720-854-389</div>
                <div>MAIL: info@chabrinagencies.co.ke</div>
            </div>
        </div>
    </div>

    <div class="yellow-line"></div>

    <div class="main-title">TENANCY AGREEMENT</div>

    <div class="intro-box">
        <div class="intro-line">
            <strong>THIS AGREEMENT</strong> is made this <strong>{{ $lease->start_date->format('jS') }}</strong> day of
            <strong>{{ $lease->start_date->format('F') }}</strong> <strong>{{ $lease->start_date->format('Y') }}</strong> between
            <strong>{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</strong> "The duly appointed Managing Agent" of the said property and:
        </div>
    </div>

    {{-- Tenant Details --}}
    <div style="margin: 18px 0;">
        <div style="margin: 7px 0;">
            <span class="label">TENANT'S NAME:</span>
            <span class="fill-line" style="min-width: 300px;">{{ $tenant->full_name }}</span>
        </div>

        <div style="margin: 7px 0;">
            <span class="label">ID:</span>
            <span class="fill-line">{{ $tenant->id_number }}</span>
            <span class="label" style="margin-left: 20px;">ADDRESS:</span>
            <span class="fill-line" style="min-width: 200px;">{{ $tenant->address ?? 'N/A' }}</span>
        </div>

        <div style="margin: 7px 0;">
            <span class="label">TEL:</span>
            <span class="fill-line">{{ $tenant->phone }}</span>
            <span class="label" style="margin-left: 20px;">PLACE OF WORK:</span>
            <span class="fill-line" style="min-width: 180px;">{{ $tenant->workplace ?? 'N/A' }}</span>
        </div>

        <div style="margin: 7px 0;">
            <span class="label">NEXT OF KIN:</span>
            <span class="fill-line">{{ $tenant->next_of_kin_name ?? 'N/A' }}</span>
            <span class="label" style="margin-left: 20px;">TEL:</span>
            <span class="fill-line">{{ $tenant->next_of_kin_phone ?? 'N/A' }}</span>
        </div>
    </div>

    {{-- Property Details Box --}}
    <div class="property-box">
        <div class="prop-row">
            <span class="label">PROPERTY NAME:</span>
            <span class="fill-line" style="min-width: 200px;">{{ $property->name ?? 'N/A' }}</span>
            <span class="label" style="margin-left: 15px;">ROOM NO:</span>
            <span class="fill-line">{{ $unit->unit_number }}</span>
        </div>

        <div class="prop-row">
            <span class="label">HOUSE DEPOSIT PAID:</span>
            <span class="fill-line">KES {{ number_format($lease->deposit_amount, 2) }}</span>
            <span class="label" style="margin-left: 15px;">RECEIPT NO:</span>
            <span class="fill-line">{{ $lease->reference_number }}</span>
        </div>

        <div class="prop-row">
            <span class="label">MONTHLY RENT:</span>
            <span class="fill-line" style="font-weight: bold; font-size: 12pt;">KES {{ number_format($lease->monthly_rent, 2) }}</span>
            <span class="label" style="margin-left: 15px;">DATE:</span>
            <span class="fill-line">{{ $lease->start_date->format('d/m/Y') }}</span>
        </div>
    </div>

    <div style="text-align: center; font-weight: bold; margin: 20px 0;">
        WHERE IT IS AGREED BETWEEN the parties as follows:-
    </div>

    <div class="conditions-title">CONDITIONS</div>

    <div class="condition">
        <span class="cond-num">1.</span>
        Rent is <span class="important">STRICTLY</span> payable on or before the <strong>1<sup>st</sup></strong> day of the month and the deadline will
        be on the <strong>5<sup>th</sup></strong> of every month during the tenancy period.
    </div>

    <div class="condition">
        <span class="cond-num">2.</span>
        An equivalent of one-month rent will be paid as deposit and a <strong>KES {{ number_format($lease->water_deposit ?? 1000, 2) }}</strong>
        electricity and water deposits payable to Chabrin Agencies Ltd bank accounts. The
        rent deposit sum is refundable at the termination of this tenancy with proper one (1)
        calendar months' written notice. The said sum may be utilized to defray any
        outstanding conservancy charges, damages or expenses which would be at all
        material times may be payable by the tenant within the tenancy period and such,
        the deposit should <span class="important">NEVER</span> be used as the last months' rent payment. Refunds done
        on <strong>25<sup>th</sup>/26<sup>th</sup></strong> of the month upon following the laid down procedures.
    </div>

    <div class="condition">
        <span class="cond-num">3.</span>
        Either party can terminate this agreement by giving a one (1) calendar Months'
        notice in writing.
    </div>

    <div class="condition">
        <span class="cond-num">4.</span>
        The property owner will only allow established occupants before renting out a unit
        in the premise.
    </div>

    <div class="condition">
        <span class="cond-num">5.</span>
        To permit the Landlord, his agents, workmen or servants at all reasonable times on
        notice from the landlord whether oral or written to enter upon the said premises or
        part thereof and execute structural or other repairs to the building.
    </div>

    <div class="condition">
        <span class="cond-num">6.</span>
        No reckless use of water will be tolerated. Only authorized occupants will enjoy this
        facility.
    </div>

    <div class="condition">
        <span class="cond-num">7.</span>
        Anti-social activities likely to inconvenience other tenants like loud music or any other
        unnecessary noise <span class="important">SHALL NOT</span> be tolerated and as such, the said behavior shall be
        deemed as breach of this agreement that shall form the basis of terminating the
        tenancy without further reference to the tenant.
    </div>

    <div class="condition">
        <span class="cond-num">8.</span>
        It will be the responsibility of every tenant to keep the premises clean.
    </div>

    <div class="condition">
        <span class="cond-num">9.</span>
        Sources of energy such as firewood, charcoal, open lamps or any other smoking
        instrument should not be used in the premises.
    </div>

    <div class="condition">
        <span class="cond-num">10.</span>
        To use the premises for private residential purposes only and not carry any form of
        business or use them as a boarding house or any other unauthorized purpose without
        the consent of the Landlord in writing.
    </div>

    <div class="condition">
        <span class="cond-num">11.</span>
        Not to make or permit to made any alterations in or additions to the said premises nor
        to erect any fixtures therein nor drive any nails, screws, bolts or wedges in the floors,
        walls or ceilings thereof without the consent in writing of the Landlord first hand and
        obtained (which consent shall not unreasonably withheld).
    </div>

    <div class="condition">
        <span class="cond-num">12.</span>
        Not to sublet or let out the space apportioned under the lease. Breach of this clause
        will lead to immediate termination of the running lease.
    </div>

    <div class="condition">
        <span class="cond-num">13.</span>
        Deposit should be updated from time to time as the house rent is adjusted.
    </div>

    <div class="condition">
        <span class="cond-num">14.</span>
        The tenant will be required to pay for any damages to the property during the tenancy
        period.
    </div>

    <div class="condition">
        <span class="cond-num">15.</span>
        The landlord reserves the right to increase rent after giving three (3) months' notice
        in writing.
    </div>

    <div class="notice-box">
        <strong>IMPORTANT NOTICE:</strong> This agreement shall commence on
        <strong>{{ $lease->start_date->format('d/m/Y') }}</strong> and continue on a month-to-month basis
        until terminated by either party as per the terms stated herein.
    </div>

    {{-- Signatures --}}
    <div class="sig-section">
        <div class="sig-row">
            <div class="sig-cell">
                <div style="font-weight: bold;">LANDLORD / AGENT</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</div>
                <div class="sig-info">Managing Agent</div>
                <div class="sig-info">Date: _______________</div>
            </div>

            <div class="sig-cell">
                <div style="font-weight: bold;">TENANT</div>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $tenant->full_name }}</div>
                <div class="sig-info">ID: {{ $tenant->id_number }}</div>
                <div class="sig-info">Date: _______________</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div>This is a legally binding document. Both parties should retain a copy for their records.</div>
        <div style="margin-top: 5px;">Lease Reference: <strong>{{ $lease->reference_number }}</strong></div>
        <div style="margin-top: 5px;">Generated on: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
