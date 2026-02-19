<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tenancy Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 40px 50px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 3px solid #FFD700;
        }
        .header-left {
            display: table-cell;
            width: 30%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 70%;
            text-align: right;
            vertical-align: top;
            font-size: 10px;
            color: #1a365d;
        }
        .logo-text {
            font-size: 12px;
            font-weight: bold;
            color: #1a365d;
        }
        .logo-sub {
            font-size: 8px;
            color: #666;
        }
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
        }
        .field-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 150px;
        }
        .field-row {
            margin-bottom: 6px;
        }
        .conditions-title {
            font-weight: bold;
            text-decoration: underline;
            margin: 15px 0 10px 0;
        }
        ol {
            margin: 0;
            padding-left: 25px;
        }
        ol li {
            margin-bottom: 8px;
            text-align: justify;
        }
        .signature-section {
            margin-top: 30px;
        }
        .signature-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 200px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.03);
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>
<body>

<div class="watermark">CHABRIN AGENCIES LTD</div>

<!-- HEADER -->
<div class="header">
    <div class="header-left">
        <div class="logo-text">CHABRIN<br>AGENCIES■■<br>LTD</div>
        <div class="logo-sub">Registered Property Management & Consultants</div>
    </div>
    <div class="header-right">
        <strong>NACICO PLAZA, LANDHIES ROAD</strong><br>
        5<sup>TH</sup> FLOOR – ROOM 517<br>
        P.O. Box 16659 – 00620<br>
        NAIROBI<br>
        <span style="color: #FFD700;">CELL: +254-720-854-389</span><br>
        <span style="color: #FFD700;">MAIL: info@chabrinagencies.co.ke</span>
    </div>
</div>

<!-- TITLE -->
<div class="title">TENANCY AGREEMENT</div>

<!-- INTRO -->
<p>
    <strong>THIS AGREEMENT</strong> is made this <span class="field-line">{{ $lease->created_at ? $lease->created_at->format('jS') : '........' }}</span> day of <span class="field-line">{{ $lease->created_at ? $lease->created_at->format('F') : '..................' }}</span> 20<span class="field-line">{{ $lease->created_at ? $lease->created_at->format('y') : '___' }}</span> between <span class="field-line">Chabrin Agencies Ltd</span> "The duly appointed Managing Agent" of the said property and:
</p>

<!-- TENANT DETAILS -->
<div class="field-row">
    <strong>TENANT'S NAME:</strong> <span class="field-line" style="min-width: 350px;">{{ $tenant->full_name ?? $tenant->name ?? '' }}</span>
</div>
<div class="field-row">
    <strong>ID</strong> <span class="field-line">{{ $tenant->id_number ?? '' }}</span> (Attach copy)
    <strong>ADDRESS:</strong> <span class="field-line">{{ $tenant->address ?? '' }}</span>
</div>
<div class="field-row">
    <strong>TEL:</strong> <span class="field-line">{{ $tenant->phone ?? '' }}</span>
    <strong>PLACE OF WORK:</strong> <span class="field-line">{{ $tenant->employer ?? '' }}</span>
</div>
<div class="field-row">
    <strong>NEXT OF KIN:</strong> <span class="field-line">{{ $tenant->next_of_kin ?? '' }}</span>
    <strong>TEL:</strong> <span class="field-line">{{ $tenant->next_of_kin_phone ?? '' }}</span>
</div>
<div class="field-row">
    <strong>PROPERTY NAME:</strong> <span class="field-line">{{ $property->name ?? '' }}</span>
    <strong>ROOM NO:</strong> <span class="field-line">{{ $unit->unit_number ?? '' }}</span>
</div>
<div class="field-row">
    <strong>HOUSE DEPOSIT PAID:</strong> <span class="field-line">{{ number_format($lease->deposit_amount, 2) }}</span>
    <strong>RECEIPT NO.</strong> <span class="field-line">................</span>
    <strong>DATE:</strong> <span class="field-line">{{ $lease->created_at ? $lease->created_at->format('d/m/Y') : '...../...../......' }}</span>
</div>

<p><strong><em>WHERE IT IS AGREED BETWEEN the parties as follows:-</em></strong></p>

<!-- CONDITIONS -->
<div class="conditions-title">CONDITIONS</div>

<ol>
    <li>Rent is <strong>STRICTLY</strong> payable on or before the 1<sup>st</sup> day of the month and the deadline will be on the <strong><u>5<sup>th</sup></u></strong> of every month during the tenancy period.</li>

    <li>An equivalent of one-month rent will be paid as deposit and a Kshs. <span class="field-line">________</span> electricity and water deposits payable to Chabrin Agencies Ltd bank accounts. The rent deposit sum is refundable at the termination of this tenancy with proper one (1) calendar months' written notice. The said sum may be utilized to defray any outstanding conservancy charges, damages or expenses which would be at all material times may be payable by the tenant within the tenancy period and such, the deposit should <strong>NEVER</strong> be used as the last months' rent payment. Refunds done on <strong><u>25<sup>th</sup>/26<sup>th</sup> of the month</u></strong> upon following the laid down procedures.</li>

    <li>Either party can terminate this agreement by giving a one (1) calendar Months' notice in writing.</li>

    <li>The property owner will only allow established occupants before renting a out a unit in the premise.</li>

    <li>To permit the Landlord, his agents, workmen or servants at all reasonable times on notice from the landlord whether oral or written to enter upon the said premises or part thereof and execute structural or other repairs to the building.</li>

    <li>No reckless use of water will be tolerated. Only authorized occupants will enjoy this facility.</li>
</ol>

<!-- PAGE 2 -->
<div style="page-break-before: always;"></div>

<div class="header">
    <div class="header-left">
        <div class="logo-text">CHABRIN<br>AGENCIES■■<br>LTD</div>
        <div class="logo-sub">Registered Property Management & Consultants</div>
    </div>
    <div class="header-right">
        <strong>NACICO PLAZA, LANDHIES ROAD</strong><br>
        5<sup>TH</sup> FLOOR – ROOM 517<br>
        P.O. Box 16659 – 00620<br>
        NAIROBI<br>
        <span style="color: #FFD700;">CELL: +254-720-854-389</span><br>
        <span style="color: #FFD700;">MAIL: info@chabrinagencies.co.ke</span>
    </div>
</div>

<ol start="7">
    <li>Anti-social activities likely to inconvenience other tenants like loud music or any other unnecessary noise <strong>SHALL NOT</strong> be tolerated and as such, the said behavior shall be deemed as breach of this agreement that shall form the basis of terminating the tenancy without further reference to the tenant.</li>

    <li>It will be the responsibility of every tenant to keep the premises clean.</li>

    <li>Sources of energy such as firewood, charcoal, open lamps or any other smoking instrument should not be used in the premises.</li>

    <li>To use the premises for private residential purposes only and not carry any form of business or use them as a boarding house or any other unauthorized purpose without the consent of the Landlord in writing.</li>

    <li>Not to make or permit to made any alterations in or additions to the said premises nor to erect any fixtures therein nor drive any nails, screws, bolts or wedges in the floors, walls or ceilings thereof without the consent in writing of the Landlord first hand and obtained(which consent shall not unreasonably withheld).</li>

    <li>Not to sublet or let out the space apportioned under the lease. Breach of this clause will lead to immediate termination of the running lease.</li>

    <li>Deposit should be updated from time to time as the house rent is adjusted.</li>

    <li>In the event of failure to pay the said rents or any other sum due under this lease within seven (7) days of the due date whether formally demanded or not the Landlord/Agent may take necessary action or sending auctioneers to the lessee to recover the said sum due as to costs and any incidentals to be borne by the lessee.</li>

    <li>The tenant/lessee shall insure his personal and household belongings and indemnify the landlord against any action claim or demand arising from any loss, damage, theft or injury to the tenant or tenant's family, licensee, invitees or servants.</li>

    <li>No extension of this agreement shall be implied even though the tenant should continue to be in possession of the said premises after the expiration of the said term.</li>

    <li>Any delay by the lessor in exercising any rights hereunder shall not be deemed to be a waiver of such rights in any way.</li>
</ol>

<p><strong><em>IN WITNESS WHEREOF</em></strong> the parties hereto set their hands and seal the day and the year herein before mentioned.</p>

<!-- SIGNATURES -->
<div class="signature-section">
    <p>SIGNED: <strong>MANAGING AGENT</strong> <span class="signature-line"></span> Date <span class="field-line">......./............/...........</span></p>
    <br><br>
    <p>SIGNED: <strong>TENANT</strong></p>
    <p>Name <span class="signature-line">{{ $tenant->names ?? $tenant->full_name ?? '' }}</span></p>
    @if (!empty($digitalSignature) && !empty($digitalSignature->signature_data))
        <img src="{{ $digitalSignature->data_uri }}"
             style="max-width:200px; max-height:80px; border-bottom:1px solid #000; display:block; margin-top:8px;"
             alt="Tenant Signature">
        <p style="font-size:9pt; color:#555; margin-top:4px;">
            Digitally signed: {{ $digitalSignature->created_at?->format('d M Y, h:i A') }}<br>
            IP: {{ $digitalSignature->ip_address ?? 'N/A' }}
        </p>
    @else
        <p>Signature <span class="field-line">............</span> Date <span class="field-line">......./............/...........</span></p>
    @endif
</div>

</body>
</html>
