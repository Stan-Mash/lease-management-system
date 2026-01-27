<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commercial Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 0;
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
            line-height: 1.5;
            color: #000;
        }

        /* Cover Page Styles */
        .cover-page {
            position: relative;
            width: 100%;
            height: 297mm;
            background: #fff;
            overflow: hidden;
            page-break-after: always;
        }

        /* Modern Geometric Shapes */
        .shape-top-left {
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 0;
            border-top: 200px solid #6c757d;
            border-right: 200px solid transparent;
            opacity: 0.15;
        }

        .shape-accent-1 {
            position: absolute;
            top: 100px;
            right: 50px;
            width: 0;
            height: 0;
            border-bottom: 120px solid #28a745;
            border-left: 120px solid transparent;
            opacity: 0.12;
        }

        .shape-accent-2 {
            position: absolute;
            bottom: 150px;
            left: 80px;
            width: 0;
            height: 0;
            border-top: 90px solid #F7941D;
            border-right: 90px solid transparent;
            opacity: 0.1;
        }

        /* Decorative Circles */
        .circle-group {
            position: absolute;
            bottom: 60px;
            right: 80px;
            display: flex;
            gap: 15px;
        }

        .circle {
            width: 25px;
            height: 25px;
            border-radius: 50%;
        }

        .circle-1 {
            background: #F7941D;
            opacity: 0.6;
        }

        .circle-2 {
            background: #28a745;
            opacity: 0.5;
        }

        .circle-3 {
            background: #6c757d;
            opacity: 0.4;
        }

        /* Logo on Cover */
        .cover-logo {
            position: absolute;
            top: 30px;
            right: 40px;
        }

        .cover-logo svg {
            width: 100px;
            height: auto;
        }

        /* Building Image Placeholder */
        .building-image {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 200px;
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .building-placeholder {
            font-size: 60px;
            color: #6c757d;
            opacity: 0.3;
        }

        /* Cover Title */
        .cover-title {
            position: absolute;
            bottom: 200px;
            left: 0;
            right: 0;
            text-align: center;
        }

        .cover-title h1 {
            font-size: 28pt;
            font-weight: bold;
            color: #2C3E50;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }

        .cover-subtitle {
            font-size: 13pt;
            color: #6c757d;
            font-weight: 600;
        }

        .cover-ref {
            margin-top: 20px;
            font-size: 11pt;
            color: #F7941D;
            font-weight: bold;
        }

        /* Regular Pages Header */
        .page-header {
            display: table;
            width: 100%;
            padding: 0.5in 0.75in 0 0.75in;
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
            margin: 8px 0.75in 18px 0.75in;
        }

        /* Content area */
        .content {
            padding: 0 0.75in;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 25px 0 15px 0;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            color: #2C3E50;
        }

        .intro-box {
            margin: 15px 0;
            line-height: 1.6;
        }

        .intro-line {
            margin: 8px 0;
        }

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

        /* Property details box */
        .property-box {
            margin: 18px 0;
            padding: 14px;
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            border-radius: 3px;
        }

        .prop-row {
            margin: 7px 0;
            font-size: 10.5pt;
        }

        /* Clause styling */
        .clause {
            margin: 15px 0;
            text-align: justify;
        }

        .clause-num {
            font-weight: bold;
            margin-right: 8px;
            display: inline-block;
            min-width: 30px;
            color: #28a745;
        }

        .sub-clause {
            margin: 8px 0 8px 40px;
            text-align: justify;
        }

        .sub-num {
            font-weight: 600;
            margin-right: 6px;
            display: inline-block;
            min-width: 30px;
        }

        /* VAT box */
        .vat-box {
            background: #fffacd;
            padding: 12px;
            margin: 18px 0;
            border-left: 4px solid #F7941D;
            border-radius: 3px;
            font-size: 10.5pt;
        }

        /* Important highlight */
        .important {
            color: #dc3545;
            font-weight: bold;
        }

        /* Signatures */
        .sig-section {
            margin-top: 45px;
        }

        .sig-row {
            display: table;
            width: 100%;
            margin: 35px 0;
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
            padding: 12px 0.75in 0 0.75in;
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

        /* Page break control */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    {{-- COVER PAGE --}}
    <div class="cover-page">
        {{-- Geometric shapes --}}
        <div class="shape-top-left"></div>
        <div class="shape-accent-1"></div>
        <div class="shape-accent-2"></div>

        {{-- Logo on cover --}}
        <div class="cover-logo">
            <svg viewBox="0 0 120 70" xmlns="http://www.w3.org/2000/svg">
                <g transform="translate(40, 10)">
                    <path d="M20,10 L35,1 L50,10 L50,35 L20,35 Z" fill="#2C3E50" stroke="#000" stroke-width="1.5"/>
                    <rect x="26" y="18" width="8" height="10" fill="#F7941D"/>
                    <rect x="36" y="18" width="8" height="10" fill="#34495E"/>
                    <rect x="28" y="28" width="14" height="7" fill="#34495E"/>
                    <polygon points="20,10 35,1 50,10 48,12 35,4 22,12" fill="#F7941D"/>
                </g>
                <text x="10" y="52" font-family="Arial" font-size="11" font-weight="bold" fill="#2C3E50">CHABRIN</text>
                <text x="10" y="62" font-family="Arial" font-size="10" font-weight="bold" fill="#2C3E50">AGENCIES</text>
                <text x="10" y="69" font-family="Arial" font-size="7" fill="#6c757d">LTD</text>
            </svg>
        </div>

        {{-- Building image placeholder --}}
        <div class="building-image">
            <div class="building-placeholder">🏢</div>
        </div>

        {{-- Cover title --}}
        <div class="cover-title">
            <h1>COMMERCIAL LEASE<br>AGREEMENT</h1>
            <div class="cover-subtitle">Property Management & Consultancy Services</div>
            <div class="cover-ref">REF: {{ $lease->reference_number }}</div>
        </div>

        {{-- Decorative circles --}}
        <div class="circle-group">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
            <div class="circle circle-3"></div>
        </div>
    </div>

    {{-- PAGE 2: AGREEMENT DETAILS --}}
    <div class="page-header">
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

    <div class="content">
        <div style="text-align: center; font-size: 15pt; font-weight: bold; margin: 20px 0 25px 0; text-transform: uppercase;">
            COMMERCIAL LEASE AGREEMENT
        </div>

        <div class="intro-box">
            <div class="intro-line">
                <strong>THIS AGREEMENT</strong> is made this <strong>{{ $lease->start_date->format('jS') }}</strong> day of
                <strong>{{ $lease->start_date->format('F') }}</strong> <strong>{{ $lease->start_date->format('Y') }}</strong> between
                <strong>{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</strong> "The duly appointed Managing Agent" (hereinafter referred to as "The Landlord")
                of the one part and:
            </div>
        </div>

        {{-- Tenant Details --}}
        <div style="margin: 20px 0;">
            <div style="margin: 8px 0;">
                <span class="label">TENANT'S NAME / BUSINESS:</span>
                <span class="fill-line" style="min-width: 320px;">{{ $tenant->full_name }}</span>
            </div>

            <div style="margin: 8px 0;">
                <span class="label">ID / REG. NO:</span>
                <span class="fill-line">{{ $tenant->id_number }}</span>
                <span class="label" style="margin-left: 20px;">KRA PIN:</span>
                <span class="fill-line" style="min-width: 180px;">{{ $tenant->kra_pin ?? 'N/A' }}</span>
            </div>

            <div style="margin: 8px 0;">
                <span class="label">ADDRESS:</span>
                <span class="fill-line" style="min-width: 280px;">{{ $tenant->address ?? 'N/A' }}</span>
            </div>

            <div style="margin: 8px 0;">
                <span class="label">TEL:</span>
                <span class="fill-line">{{ $tenant->phone }}</span>
                <span class="label" style="margin-left: 20px;">EMAIL:</span>
                <span class="fill-line" style="min-width: 200px;">{{ $tenant->email ?? 'N/A' }}</span>
            </div>

            <div style="margin: 8px 0;">
                <span class="label">BUSINESS NATURE:</span>
                <span class="fill-line" style="min-width: 280px;">{{ $tenant->business_nature ?? 'N/A' }}</span>
            </div>
        </div>

        {{-- Property Details Box --}}
        <div class="property-box">
            <div class="prop-row">
                <span class="label">PROPERTY NAME:</span>
                <span class="fill-line" style="min-width: 200px;">{{ $property->name ?? 'N/A' }}</span>
                <span class="label" style="margin-left: 15px;">UNIT NO:</span>
                <span class="fill-line">{{ $unit->unit_number }}</span>
            </div>

            <div class="prop-row">
                <span class="label">FLOOR AREA:</span>
                <span class="fill-line">{{ $unit->size ?? 'N/A' }} sq. ft.</span>
                <span class="label" style="margin-left: 15px;">SECURITY DEPOSIT:</span>
                <span class="fill-line">KES {{ number_format($lease->deposit_amount, 2) }}</span>
            </div>

            <div class="prop-row">
                <span class="label">MONTHLY RENT (Excl. VAT):</span>
                <span class="fill-line" style="font-weight: bold; font-size: 12pt;">KES {{ number_format($lease->monthly_rent, 2) }}</span>
            </div>

            <div class="prop-row">
                <span class="label">LEASE COMMENCEMENT:</span>
                <span class="fill-line">{{ $lease->start_date->format('d/m/Y') }}</span>
                <span class="label" style="margin-left: 15px;">LEASE END:</span>
                <span class="fill-line">{{ $lease->end_date->format('d/m/Y') }}</span>
            </div>
        </div>

        <div class="vat-box">
            <strong>VAT NOTICE:</strong> Value Added Tax (VAT) at the prevailing rate (currently 16%) shall be charged on the monthly rent
            and is payable by the Tenant in addition to the rent stated above.
        </div>

        <div style="text-align: center; font-weight: bold; margin: 25px 0 20px 0;">
            WHERE IT IS AGREED BETWEEN the parties as follows:-
        </div>

        <div class="section-title">TERMS AND CONDITIONS</div>

        <div class="clause">
            <span class="clause-num">1.</span>
            <strong>LEASE TERM:</strong> The Landlord agrees to let and the Tenant agrees to take the demised premises
            for a period commencing from <strong>{{ $lease->start_date->format('d/m/Y') }}</strong> and ending on
            <strong>{{ $lease->end_date->format('d/m/Y') }}</strong>.
        </div>

        <div class="clause">
            <span class="clause-num">2.</span>
            <strong>RENT PAYMENT:</strong> Rent is <span class="important">STRICTLY</span> payable in advance on or before
            the <strong>1<sup>st</sup></strong> day of each month. Late payment beyond the <strong>5<sup>th</sup></strong>
            of the month shall attract a penalty of <strong>2%</strong> of the monthly rent per day delayed.
        </div>

        <div class="clause">
            <span class="clause-num">3.</span>
            <strong>SECURITY DEPOSIT:</strong> The Tenant has paid a refundable security deposit equivalent to three (3) months' rent
            amounting to <strong>KES {{ number_format($lease->deposit_amount, 2) }}</strong>. The deposit shall be refundable
            at the end of the lease term subject to:
            <div class="sub-clause">
                <span class="sub-num">(a)</span> Full settlement of all outstanding rent and charges;
            </div>
            <div class="sub-clause">
                <span class="sub-num">(b)</span> No damages to the premises beyond normal wear and tear;
            </div>
            <div class="sub-clause">
                <span class="sub-num">(c)</span> Proper notice of termination as per this agreement;
            </div>
            <div class="sub-clause">
                <span class="sub-num">(d)</span> Surrender of the premises in good condition.
            </div>
        </div>

        <div class="clause">
            <span class="clause-num">4.</span>
            <strong>PERMITTED USE:</strong> The premises shall be used solely for the purpose of
            <strong>{{ $tenant->business_nature ?? 'Commercial Business' }}</strong> and for no other purpose without the
            prior written consent of the Landlord. No hazardous, illegal, or immoral activities shall be conducted on the premises.
        </div>

        <div class="clause">
            <span class="clause-num">5.</span>
            <strong>SERVICE CHARGES:</strong> The Tenant shall pay monthly service charges for common area maintenance,
            security, garbage collection, and other shared facilities. Service charges are reviewed annually and are payable
            alongside monthly rent.
        </div>

        <div class="clause">
            <span class="clause-num">6.</span>
            <strong>UTILITIES:</strong> The Tenant shall be responsible for all utility charges including but not limited to
            electricity, water, internet, and telephone services. Meters shall be read monthly and bills settled promptly.
        </div>

        <div class="clause">
            <span class="clause-num">7.</span>
            <strong>MAINTENANCE & REPAIRS:</strong> The Tenant shall maintain the interior of the premises in good condition
            and shall be responsible for all internal repairs. The Landlord shall be responsible for structural repairs and
            maintenance of common areas.
        </div>

        <div class="clause">
            <span class="clause-num">8.</span>
            <strong>ALTERATIONS:</strong> The Tenant shall not make any structural alterations, additions, or improvements
            to the premises without the prior written consent of the Landlord. Any approved alterations shall become the
            property of the Landlord upon lease termination.
        </div>

        <div class="clause">
            <span class="clause-num">9.</span>
            <strong>SIGNAGE:</strong> The Tenant may install business signage subject to the Landlord's approval of design,
            size, and location. All signage shall comply with local authority regulations and building guidelines.
        </div>

        <div class="clause">
            <span class="clause-num">10.</span>
            <strong>INSURANCE:</strong> The Tenant shall maintain comprehensive insurance covering public liability, plate glass,
            stock, and contents. The Landlord shall insure the building structure. Both parties shall provide proof of insurance
            to each other upon request.
        </div>

        <div class="clause">
            <span class="clause-num">11.</span>
            <strong>ACCESS:</strong> The Landlord or his authorized agents shall have the right to enter the premises at
            reasonable times upon giving <strong>24 hours' notice</strong> for the purpose of inspection, repairs, or showing
            the premises to prospective tenants or purchasers.
        </div>

        <div class="clause">
            <span class="clause-num">12.</span>
            <strong>SUBLETTING:</strong> The Tenant shall <span class="important">NOT</span> assign, sublet, or part with
            possession of the premises or any part thereof without the prior written consent of the Landlord. Unauthorized
            subletting shall constitute a material breach of this agreement.
        </div>

        <div class="clause">
            <span class="clause-num">13.</span>
            <strong>RENT REVIEW:</strong> The rent shall be subject to review annually or as mutually agreed. The Landlord
            shall give the Tenant <strong>three (3) months' written notice</strong> of any proposed rent increase.
        </div>

        <div class="clause">
            <span class="clause-num">14.</span>
            <strong>NOTICE OF TERMINATION:</strong> Either party may terminate this lease by giving the other party
            <strong>three (3) months' written notice</strong>. Notice must be in writing and delivered by registered mail
            or hand delivery with acknowledgment.
        </div>

        <div class="clause">
            <span class="clause-num">15.</span>
            <strong>DEFAULT & REMEDIES:</strong> In the event of default by the Tenant including non-payment of rent,
            breach of covenants, or illegal use of premises, the Landlord may:
            <div class="sub-clause">
                <span class="sub-num">(a)</span> Demand immediate payment of all arrears plus penalties;
            </div>
            <div class="sub-clause">
                <span class="sub-num">(b)</span> Terminate the lease immediately by written notice;
            </div>
            <div class="sub-clause">
                <span class="sub-num">(c)</span> Forfeit the security deposit;
            </div>
            <div class="sub-clause">
                <span class="sub-num">(d)</span> Take legal action for recovery of premises and outstanding amounts.
            </div>
        </div>

        <div class="clause">
            <span class="clause-num">16.</span>
            <strong>FORCE MAJEURE:</strong> Neither party shall be liable for failure to perform obligations due to circumstances
            beyond their reasonable control including acts of God, war, strikes, government regulations, or natural disasters.
        </div>

        <div class="clause">
            <span class="clause-num">17.</span>
            <strong>GOVERNING LAW:</strong> This agreement shall be governed by and construed in accordance with the laws
            of the Republic of Kenya. Any disputes shall be subject to the jurisdiction of Kenyan courts.
        </div>

        <div class="vat-box" style="margin-top: 25px;">
            <strong>IMPORTANT:</strong> This is a legally binding commercial lease agreement. Both parties should seek
            independent legal advice before signing. All payments should be made to Chabrin Agencies Ltd bank accounts only.
        </div>

        {{-- Signatures --}}
        <div class="sig-section">
            <div style="font-weight: bold; font-size: 12pt; margin-bottom: 20px; text-align: center;">
                IN WITNESS WHEREOF the parties have executed this agreement on the date first above written.
            </div>

            <div class="sig-row">
                <div class="sig-cell">
                    <div style="font-weight: bold;">LANDLORD / AGENT</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $landlord->name ?? 'CHABRIN AGENCIES LTD' }}</div>
                    <div class="sig-info">Managing Agent</div>
                    <div class="sig-info">Date: _______________</div>
                    <div class="sig-info" style="margin-top: 8px;">Official Stamp:</div>
                </div>

                <div class="sig-cell">
                    <div style="font-weight: bold;">TENANT / AUTHORIZED SIGNATORY</div>
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $tenant->full_name }}</div>
                    <div class="sig-info">ID/Reg: {{ $tenant->id_number }}</div>
                    <div class="sig-info">Date: _______________</div>
                    <div class="sig-info" style="margin-top: 8px;">Company Stamp (if applicable):</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div>This is a legally binding commercial lease agreement. Both parties should retain a copy for their records.</div>
            <div style="margin-top: 5px;">Lease Reference: <strong>{{ $lease->reference_number }}</strong></div>
            <div style="margin-top: 5px;">Generated on: {{ now()->format('d/m/Y H:i') }}</div>
            <div style="margin-top: 5px;">Page 2 of 2</div>
        </div>
    </div>
</body>
</html>
