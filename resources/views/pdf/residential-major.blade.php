<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tenancy Lease Agreement - {{ $lease->reference_number }}</title>
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
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
        }
        .section-title {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .field-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 120px;
        }
        .field-row {
            margin-bottom: 6px;
        }
        .center {
            text-align: center;
        }
        ol {
            margin: 0;
            padding-left: 25px;
        }
        ol li {
            margin-bottom: 8px;
            text-align: justify;
        }
        .schedule-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
        }
        .signature-section {
            margin-top: 30px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
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
<div class="title">TENANCY LEASE AGREEMENT</div>

<p class="center"><strong>BETWEEN</strong></p>

<p>
    1. <span class="field-line" style="min-width: 300px;">{{ $landlord->name ?? '' }}</span> c/o<br>
    <strong>MANAGING AGENT: CHABRIN AGENCIES LTD</strong><br>
    P O BOX 16659-00620<br>
    NAIROBI
</p>

<p class="center"><strong>AND</strong></p>

<div class="field-row">
    2. <strong>TENANT:</strong> <span class="field-line" style="min-width: 400px;">{{ $tenant->full_name ?? $tenant->name ?? '' }}</span>
</div>
<div class="field-row">
    <strong>ID NO:</strong> <span class="field-line">{{ $tenant->id_number ?? '' }}</span> (Attach copy)
    <strong>Tel:</strong> <span class="field-line">{{ $tenant->phone ?? '' }}</span>
</div>
<div class="field-row">
    <strong>ADDRESS:</strong> <span class="field-line" style="min-width: 350px;">{{ $tenant->address ?? '' }}</span>
</div>
<div class="field-row">
    <strong>NEXT OF KIN:</strong> <span class="field-line">{{ $tenant->next_of_kin ?? '' }}</span>
    <strong>Tel:</strong> <span class="field-line">{{ $tenant->next_of_kin_phone ?? '' }}</span>
</div>

<p><strong>IN RESPECT OF RESIDENTIAL PREMISES DESIGNED AS:</strong></p>
<div class="field-row">
    <strong>PLOT NO:</strong> <span class="field-line">{{ $property->plot_number ?? '' }}</span>
    <strong>Flat no:</strong> <span class="field-line">{{ $unit->unit_number ?? '' }}</span>
</div>

<p>
    This tenancy agreement is made on the <span class="field-line">{{ $lease->created_at ? $lease->created_at->format('d / m / Y') : '___/___/___' }}</span>
    between <span class="field-line">{{ $landlord->name ?? '' }}</span> c/o CHABRIN AGENCIES LTD of Post Office number 16659-00620 Nairobi In the Republic of Kenya (herein called "the managing agent" which expression shall where the context so admits include its successors and assigns) of the one part and <span class="field-line">{{ $tenant->full_name ?? $tenant->name ?? '' }}</span> of ID No <span class="field-line">{{ $tenant->id_number ?? '' }}</span> Post Office number <span class="field-line">{{ $tenant->po_box ?? '' }}</span> (Hereafter called "the tenant" which expression shall where the context so admits include his/her personal representatives and assigns) of the other part.
</p>

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

<p><strong>NOW THIS TENANCY AGREEMENT WITNESSES AS FOLLOWS:</strong></p>

<ol>
    <li>That landlord hereby grants and the tenant hereby accepts a lease of the premises (hereinafter called the "premises") described in the schedule hereto for the term of and at the rent specified in the said schedule, payable as provided in the said schedule subject to the covenants agreements conditions, stipulations and provisions contained hereinafter.</li>
</ol>

<p><strong>2. The tenants covenants with the landlord as follows:-</strong></p>
<ol type="a">
    <li>To pay the rent as stated in the schedule without any deductions whatsoever to the landlord or the landlord's duly appointed agents.</li>

    <li>On or before execution of this agreement to pay the landlord or his agents Kenya Shillings <span class="field-line">{{ number_format($lease->deposit_amount, 2) }}</span> Refundable security bond to be held by the said landlord or his agent until this agreement is terminated. The said deposit shall be refunded to the tenant without interest on termination of this agreement after the due performance of all the terms and conditions of this agreement by the tenant to the satisfaction of the landlord. Should the tenant default in such performance, the said deposit will be utilized by the landlord in performance in the said terms and conditions on behalf of the tenant.</li>

    <li>The tenant has examined and knows the condition of premises and has received the same in good order and repairs except as herein otherwise specified at the execution of this lease and upon the termination of this lease in any way, tenant will immediately yield up premises to Lessor or his Agent in as good condition as when the same as entered upon by tenant and in particular the tenant shall be required to repaint the interior walls and fittings with first quality paint to restore them as they were at the commencement of the tenancy. The repainting and repair shall be carried by a contractor approved and appointed by the Lessor or his agent.</li>

    <li>To pay all electricity and water conservancy charges in respect of the said premises throughout the terms hereby created or to the date of its sooner termination as hereinafter provided.</li>

    <li>To keep the interior of the said premises including all doors, windows, locks, fasteners, keys, water taps and all internal sanitary apparatus and electric light fittings in good and tenantable repair and proper working order and condition (fair wear and tear expected).</li>

    <li>Not to make alterations in or additions to the said premise without the landlord's prior consent in writing.</li>
</ol>

<!-- PAGE 3 -->
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

<ol type="a" start="7">
    <li>Not without the landlord's prior consent in writing to alter or interfere with the plumbing or electrical installations other than to keep in repair and to replace as and when necessary all switches fuses and elements forming part of the electrical installations.</li>

    <li>To replace and be responsible for the cost of any keys which are damaged or lost and their appropriate interior and exterior doors and locks.</li>

    <li>To permit the landlord or the landlord's agent to enter and view the condition of the said premises and upon notice given by the landlord forthwith to repair in accordance with such notice and in the event of the tenant not carrying out such repairs within fourteen days of the said notice the cost shall be a debt due from the landlord and shall be forthwith recoverable by action as rent.</li>

    <li>To use the premises as a residential premises for the tenant only.</li>

    <li>Not to permit any sale by auction to be held upon the said premises.</li>

    <li>Not to suffer any part of the said premises to be used as to cause annoyance or inconvenience to the occupiers of the adjacent or neighboring flat or premises.</li>

    <li>Not to suffer any part of the said premises to be used for any illegal purpose.</li>

    <li>Not to assign underlet or part with possession of any part of the said premises without the prior consent in writing or the landlord, first had and obtained.</li>

    <li>During the last one (1) months of the term hereby created to permit the landlord to affix upon the said premises a notice for re-letting and to permit persons with authority from the landlord or the landlord's agent or agents at reasonable times to view the said premises by prior appointment.</li>

    <li>To yield up the said premises with all fixtures (other than the tenant's fixtures) and additions at the expiration or sooner determination of the tenancy in good and tenantable repair and condition and good as the tenant found them at the commencement of the lease.</li>

    <li>In case of breach of this tenancy agreement the tenant or the landlord is entitled to one month's notice in writing or paying one month rent in lieu thereof to terminate the term hereby created.</li>

    <li>To pay service charge e.g. security and garbage collection. The responsibility to appoint agents of these services rest on tenants unless where the landlord is requested to assist.</li>
</ol>

<!-- PAGE 4 -->
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

<ol type="a" start="19">
    <li>All payments are strictly made to our accounts as provided. Personal cheques are not acceptable. Any cheque returned to us unpaid will attract an immediate penalty of Kshs 3,500.</li>
</ol>

<p><strong>3. The landlord covenant with the tenant as follows:</strong></p>
<ol type="a">
    <li>To permit the tenant to peacefully hold and enjoy the said premises during the said term without any interruption by the landlord or any person or agents rightfully claiming under or in trust of the landlord, so long as the tenant pays the rent hereby reserved and performs and observes the several covenants and the conditions herein contained.</li>

    <li>To keep the walls, roof and structure of the premises in good and tenantable state of repair and maintenance.</li>

    <li>To keep adequately lighted, cleaned and in good state the repair and condition the entrance halls and all common area of the said premises.</li>
</ol>

<ol start="4">
    <li>The landlord shall have a right of re-entry and possession if any rent shall not have been paid as agreed or on breach or non-observance by the tenant of any covenant herein contained or on bankruptcy or composition with creditors or suffering distress or execution. In that event this agreement shall stand terminated automatically, without prejudice to landlord's rights under this agreement.</li>

    <li>In case of the premises being required for statutory duties or re-construction the landlord shall give the tenant notice not more than six months from the date of service.</li>

    <li>Any party hereto wishing to terminate the tenancy created hereby shall serve upon the other party written notice of his/her intention to do so and such notice shall be for a period of not less than one(1) calendar month.</li>

    <li>Service under this lease shall be sufficiently affected if sent to any party and registered post or left at the party's last known address in Kenya. The date of the posted service is the date when the notice is posted as indicated by postal stamp on the envelope or the Lessee notice when received by the Lessor.</li>
</ol>

<!-- PAGE 5 - SCHEDULE & SIGNATURES -->
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

<div class="schedule-title">THE SCHEDULE</div>

<ol type="a">
    <li>The date of commencement of the lease is <span class="field-line">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '............/.................../................' }}</span></li>

    <li>The term of tenancy is periodic tenancy.</li>

    <li>The monthly rent is Kshs <span class="field-line">{{ number_format($lease->monthly_rent, 2) }}</span></li>

    <li>The rent shall be reviewed after each calendar year to the market rates or to such a reasonable figure and the tenant shall henceforth pay the reviewed rent.</li>

    <li>The rent is payable monthly in advance by 1<sup>st</sup> day and the deadline will be 5<sup>th</sup> day of each calendar month.</li>

    <li>The premise is designed as Plot No. <span class="field-line">{{ $property->plot_number ?? '' }} - {{ $unit->unit_number ?? '' }}</span></li>
</ol>

<p><strong>IN WITNESS WHEREOF</strong> this agreement was duly executed by the parties hereto the day and year first above written.</p>

<div class="signature-section">
    <table width="100%">
        <tr>
            <td width="50%">
                <p>Signed by the Managing Agents )</p>
                <p>(For the Landlord)</p>
                <p>The said )</p>
                <br>
                <p>In the presence of ) <span class="signature-line"></span></p>
            </td>
            <td width="50%">
                <p><span class="signature-line"></span></p>
            </td>
        </tr>
    </table>

    <br><br>

    <table width="100%">
        <tr>
            <td width="50%">
                <p>Signed by the tenant )</p>
                <p>The said ID NO. {{ $tenant->id_number ?? '' }} )</p>
                <br>
                <p>In the presence of: ) <span class="signature-line"></span></p>
            </td>
            <td width="50%">
                <p><span class="signature-line"></span></p>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
