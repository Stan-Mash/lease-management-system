<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tenancy Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .page {
            padding: 16mm 18mm 14mm 18mm;
        }
        /* HEADER */
        .header-wrap { display: table; width: 100%; margin-bottom: 2px; }
        .header-left  { display: table-cell; width: 40%; vertical-align: top; }
        .header-right {
            display: table-cell; width: 60%;
            text-align: right; vertical-align: top;
            font-size: 9pt; font-weight: bold; color: #b8880c;
        }
        .logo-inner { display: table; }
        .logo-icon-cell { display: table-cell; vertical-align: top; padding-right: 5px; }
        .logo-text-cell { display: table-cell; vertical-align: top; font-size: 9.5pt; line-height: 1.2; }
        .logo-name { font-weight: bold; color: #1a3060; font-size: 10pt; }
        .logo-reg  { font-size: 7pt; color: #333; }
        hr.gold-thick { border: none; border-top: 4px solid #c8a020; margin: 3px 0 1px 0; }
        hr.gold-thin  { border: none; border-top: 1.5px solid #c8a020; margin: 1px 0 10px 0; }

        /* WATERMARK */
        .watermark {
            position: fixed; top: 34%; left: 8%; width: 84%;
            text-align: center; font-size: 66pt; font-weight: bold;
            color: rgba(0,0,0,0.04); transform: rotate(-30deg);
            -webkit-transform: rotate(-30deg); z-index: -1;
            letter-spacing: 4px; line-height: 1.1;
        }

        /* QR + REF fixed top-right */
        .qr-fixed {
            position: fixed; top: 10mm; right: 10mm;
            width: 24mm; height: 24mm;
        }
        .qr-fixed svg { width: 24mm; height: 24mm; }
        .ref-tag {
            position: fixed; top: 36mm; right: 8mm;
            font-size: 6pt; color: #777; text-align: right;
            font-family: monospace; width: 28mm;
        }

        /* TITLE */
        .doc-title {
            text-align: center; font-size: 12.5pt;
            font-weight: bold; text-decoration: underline;
            margin: 10px 0 6px 0;
        }
        .centre-bold { text-align: center; font-weight: bold; margin: 4px 0 8px 0; }

        /* FIELDS */
        .uline { border-bottom: 1px solid #000; display: inline-block; min-width: 120px; }
        .uline-wide { border-bottom: 1px solid #000; display: inline-block; min-width: 260px; }
        .uline-full { border-bottom: 1px solid #000; display: inline-block; width: 98%; }

        /* BODY */
        p.body { text-align: justify; margin: 6px 0; }
        .field-row { margin-bottom: 5px; }
        strong.lbl { font-weight: bold; }

        /* LISTS */
        ol.main-list { margin: 0; padding-left: 20px; }
        ol.main-list > li { margin-bottom: 8px; text-align: justify; }
        ol.alpha-list { list-style-type: lower-alpha; margin: 6px 0 0 0; padding-left: 20px; }
        ol.alpha-list > li { margin-bottom: 6px; text-align: justify; }

        /* SCHEDULE */
        .schedule-title {
            text-align: center; font-weight: bold;
            text-decoration: underline; font-size: 11pt;
            margin: 12px 0 10px 0;
        }
        ol.sched { list-style-type: lower-alpha; margin: 0; padding-left: 22px; }
        ol.sched > li { margin-bottom: 6px; text-align: justify; }

        /* SIGNATURES */
        .sig-row { display: table; width: 100%; margin-bottom: 12px; }
        .sig-lbl  { display: table-cell; width: 52%; vertical-align: top; line-height: 1.9; }
        .sig-box  { display: table-cell; width: 48%; vertical-align: top; padding-top: 2px; }
        .sig-line-el { border-bottom: 1px solid #000; width: 100%; display: block; min-height: 22px; }
        .sig-img  { max-width: 220px; max-height: 75px; border-bottom: 1px solid #000; display: block; }
        .sig-meta { font-size: 8pt; color: #444; margin-top: 3px; }
        .exec-record {
            border: 1px solid #888; padding: 7px 10px;
            margin-top: 10px; font-size: 8.5pt; background: #fafafa;
        }
        .exec-record b { display: block; font-size: 9pt; margin-bottom: 4px; }

        .page-break { page-break-before: always; }
        sup { font-size: 7pt; vertical-align: super; }
    </style>
</head>
<body>

{{-- QR code --}}
@php $qr = \App\Services\QRCodeService::generateForLease($lease, false); @endphp
<div class="qr-fixed">{!! $qr['svg'] !!}</div>
<div class="ref-tag">
    Ref: {{ $lease->reference_number }}<br>
    @if($lease->serial_number)S/N: {{ $lease->serial_number }}@endif
</div>

<div class="watermark">CHABRIN<br>AGENCIES<br>LTD</div>

{{-- ═══════════ PAGE 1 ═══════════ --}}
<div class="page">

    {{-- HEADER --}}
    <div class="header-wrap">
        <div class="header-left">
            <div class="logo-inner">
                <div class="logo-icon-cell">
                    <span style="font-size:19pt;color:#4a7c3f;font-weight:bold;line-height:1;">&#9632;&#9632;<br><span style="font-size:13pt;color:#1a3060;">&#8962;</span></span>
                </div>
                <div class="logo-text-cell">
                    <div class="logo-name">CHABRIN<br>AGENCIES<span style="font-size:8pt;">&#9644;</span><br>LTD</div>
                    <div class="logo-reg">Registered Property Management &amp; Consultants</div>
                </div>
            </div>
        </div>
        <div class="header-right">
            NACICO PLAZA, LANDHIES ROAD<br>
            5<sup>TH</sup> FLOOR &ndash; ROOM 517<br>
            P.O. Box 16659 &ndash; 00620<br>
            NAIROBI<br>
            CELL : +254-720-854-389<br>
            MAIL: info@chabrinagencies.co.ke
        </div>
    </div>
    <hr class="gold-thick"><hr class="gold-thin">

    <div class="doc-title">TENANCY LEASE AGREEMENT</div>
    <div class="centre-bold">BETWEEN</div>

    <div class="field-row">
        <strong>1.</strong>&nbsp;
        <span class="uline-wide">{{ $landlord->name ?? '' }}</span>&nbsp; c/o<br>
        <strong>MANAGING AGENT: CHABRIN AGENCIES LTD</strong><br>
        P O BOX 16659-00620<br>
        NAIROBI
    </div>

    <div class="centre-bold">AND</div>

    <div class="field-row">
        <strong>2.&nbsp;&nbsp;TENANT:</strong>
        <span class="uline-full">{{ $tenant->names ?? $tenant->full_name ?? '' }}</span>
    </div>
    <div class="field-row">
        <strong class="lbl">ID NO:</strong>
        <span class="uline" style="min-width:160px;">{{ $tenant->national_id ?? $tenant->id_number ?? '' }}</span>
        <strong>(Attach copy)</strong>
        <strong class="lbl"> Tel:</strong>
        <span class="uline" style="min-width:140px;">{{ $tenant->mobile_number ?? $tenant->phone_number ?? '' }}</span>
    </div>
    <div class="field-row">
        <strong class="lbl">ADDRESS:</strong>
        <span class="uline-full">{{ $tenant->address ?? '' }}</span>
    </div>
    <div class="field-row">
        <strong class="lbl">NEXT OF KIN:</strong>
        <span class="uline" style="min-width:220px;">{{ $tenant->next_of_kin ?? '' }}</span>
        <strong class="lbl"> Tel:</strong>
        <span class="uline" style="min-width:130px;">{{ $tenant->next_of_kin_phone ?? '' }}</span>
    </div>
    <div class="field-row" style="margin-top:10px;">
        <strong>IN RESPECT OF RESIDENTIAL PREMISES DESIGNED AS:</strong>
    </div>
    <div class="field-row">
        <strong class="lbl">PLOT NO:</strong>
        <span class="uline" style="min-width:160px;">{{ $property->plot_number ?? $property->reference_number ?? '' }}</span>
        &nbsp;&nbsp;
        <strong class="lbl">Flat no:</strong>
        <span class="uline" style="min-width:120px;">{{ $unit->unit_number ?? '' }}</span>
    </div>

    <p class="body" style="margin-top:12px;">
        This tenancy agreement is made on the
        <span class="uline" style="min-width:36px;">{{ $lease->created_at ? $lease->created_at->format('d') : '' }}</span> /
        <span class="uline" style="min-width:36px;">{{ $lease->created_at ? $lease->created_at->format('m') : '' }}</span> /
        <span class="uline" style="min-width:60px;">{{ $lease->created_at ? $lease->created_at->format('Y') : '' }}</span> /
        between <span class="uline" style="min-width:180px;">{{ $landlord->name ?? '' }}</span> c/o
        CHABRIN AGENCIES LTD of Post Office number 16659-00620 Nairobi In the Republic of Kenya
        (herein called &ldquo;the managing agent&rdquo; which expression shall where the context so admits include
        its successors and assigns) of the one part and
        <span class="uline" style="min-width:170px;">{{ $tenant->names ?? $tenant->full_name ?? '' }}</span>
        of ID No <span class="uline" style="min-width:110px;">{{ $tenant->national_id ?? $tenant->id_number ?? '' }}</span>
        Post Office number <span class="uline" style="min-width:100px;">{{ $tenant->po_box ?? '' }}</span>
        (Hereafter called &ldquo;the tenant&rdquo; which expression shall where the context so admits include his/her
        personal representatives and assigns) of the other part.
    </p>

</div>

{{-- ═══════════ PAGE 2 ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap">
        <div class="header-left"><div class="logo-inner"><div class="logo-icon-cell"><span style="font-size:19pt;color:#4a7c3f;font-weight:bold;line-height:1;">&#9632;&#9632;<br><span style="font-size:13pt;color:#1a3060;">&#8962;</span></span></div><div class="logo-text-cell"><div class="logo-name">CHABRIN<br>AGENCIES<span style="font-size:8pt;">&#9644;</span><br>LTD</div><div class="logo-reg">Registered Property Management &amp; Consultants</div></div></div></div>
        <div class="header-right">NACICO PLAZA, LANDHIES ROAD<br>5<sup>TH</sup> FLOOR &ndash; ROOM 517<br>P.O. Box 16659 &ndash; 00620<br>NAIROBI<br>CELL : +254-720-854-389<br>MAIL: info@chabrinagencies.co.ke</div>
    </div>
    <hr class="gold-thick"><hr class="gold-thin">

    <p class="body" style="font-weight:bold;">NOW THIS TENANCY AGREEMENT WITNESSES AS FOLLOWS:</p>

    <ol class="main-list">
        <li>That landlord hereby grants and the tenant hereby accepts a lease of the premises
            (hereinafter called the &ldquo;premises&rdquo;) described in the schedule hereto for the term of and at
            the rent specified in the said schedule, payable as provided in the said schedule subject to
            the covenants agreements conditions, stipulations and provisions contained hereinafter.</li>

        <li><strong>The tenants covenants with the landlord as follows:-</strong>
            <ol class="alpha-list">
                <li>To pay the rent as stated in the schedule without any deductions whatsoever to the
                    landlord or the landlord&rsquo;s duly appointed agents.</li>
                <li>On or before execution of this agreement to pay the landlord or his agents Kenya
                    Shillings <span class="uline" style="min-width:160px;">{{ number_format($lease->deposit_amount, 2) }}</span>
                    Refundable security bond to be held by the said landlord or his agent until this agreement
                    is terminated. The said deposit shall be refunded to the tenant without interest on
                    termination of this agreement after the due performance of all the terms and conditions of
                    this agreement by the tenant to the satisfaction of the landlord. Should the tenant default
                    in such performance, the said deposit will be utilized by the landlord in performance in the
                    said terms and conditions on behalf of the tenant.</li>
                <li>The tenant has examined and knows the condition of premises and has received the same
                    in good order and repairs except as herein otherwise specified at the execution of this lease
                    and upon the termination of this lease in any way, tenant will immediately yield up premises
                    to Lessor or his Agent in as good condition as when the same as entered upon by tenant
                    and in particular the tenant shall be required to repaint the interior walls and fittings with
                    first quality paint to restore them as they were at the commencement of the tenancy. The
                    repainting and repair shall be carried by a contractor approved and appointed by the
                    Lessor or his agent.</li>
                <li>To pay all electricity and water conservancy charges in respect of the said premises
                    throughout the terms hereby created or to the date of its sooner termination as hereinafter
                    provided.</li>
                <li>To keep the interior of the said premises including all doors, windows, locks, fasteners,
                    keys, water taps and all internal sanitary apparatus and electric light fittings in good and
                    tenantable repair and proper working order and condition (fair wear and tear expected).</li>
                <li>Not to make alterations in or additions to the said premise without the landlord&rsquo;s prior
                    consent in writing.</li>
            </ol>
        </li>
    </ol>

</div>

{{-- ═══════════ PAGE 3 ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap">
        <div class="header-left"><div class="logo-inner"><div class="logo-icon-cell"><span style="font-size:19pt;color:#4a7c3f;font-weight:bold;line-height:1;">&#9632;&#9632;<br><span style="font-size:13pt;color:#1a3060;">&#8962;</span></span></div><div class="logo-text-cell"><div class="logo-name">CHABRIN<br>AGENCIES<span style="font-size:8pt;">&#9644;</span><br>LTD</div><div class="logo-reg">Registered Property Management &amp; Consultants</div></div></div></div>
        <div class="header-right">NACICO PLAZA, LANDHIES ROAD<br>5<sup>TH</sup> FLOOR &ndash; ROOM 517<br>P.O. Box 16659 &ndash; 00620<br>NAIROBI<br>CELL : +254-720-854-389<br>MAIL: info@chabrinagencies.co.ke</div>
    </div>
    <hr class="gold-thick"><hr class="gold-thin">

    <ol class="alpha-list" style="margin: 0; padding-left: 22px;">
        <li value="7">Not without the landlord&rsquo;s prior consent in writing to alter or interfere with the plumbing
            or electrical installations other than to keep in repair and to replace as and when necessary
            all switches fuses and elements forming part of the electrical installations.</li>
        <li>To replace and be responsible for the cost of any keys which are damaged or lost and their
            appropriate interior and exterior doors and locks.</li>
        <li>To permit the landlord or the landlord&rsquo;s agent to enter and view the condition of the said
            premises and upon notice given by the landlord forthwith to repair in accordance with such
            notice and in the event of the tenant not carrying out such repairs within fourteen days of the
            said notice the cost shall be a debt due from the landlord and shall be forthwith recoverable
            by action as rent.</li>
        <li>To use the premises as a residential premises for the tenant only.</li>
        <li>Not to permit any sale by auction to be held upon the said premises.</li>
        <li>Not to suffer any part of the said premises to be used as to cause annoyance or inconvenience
            to the occupiers of the adjacent or neighboring flat or premises.</li>
        <li>Not to suffer any part of the said premises to be used for any illegal purpose.</li>
        <li>Not to assign underlet or part with possession of any part of the said premises without the
            prior consent in writing or the landlord, first had and obtained.</li>
        <li>During the last one (1) months of the term hereby created to permit the landlord to affix
            upon the said premises a notice for re-letting and to permit persons with authority from the
            landlord or the landlord&rsquo;s agent or agents at reasonable times to view the said premises by
            prior appointment.</li>
        <li>To yield up the said premises with all fixtures (other than the tenant&rsquo;s fixtures) and additions
            at the expiration or sooner determination of the tenancy in good and tenantable repair and
            condition and good as the tenant found them at the commencement of the lease.</li>
        <li>In case of breach of this tenancy agreement the tenant or the landlord is entitled to one
            month&rsquo;s notice in writing or paying one month rent in lieu thereof to terminate the term hereby
            created.</li>
        <li>To pay service charge e.g. security and garbage collection. The responsibility to appoint
            agents of these services rest on tenants unless where the landlord is requested to assist.</li>
        <li>All payments are strictly made to our accounts as provided. Personal cheques are not
            acceptable. Any cheque returned to us unpaid will attract an immediate penalty of Kshs
            3,500.</li>
    </ol>

</div>

{{-- ═══════════ PAGE 4 ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap">
        <div class="header-left"><div class="logo-inner"><div class="logo-icon-cell"><span style="font-size:19pt;color:#4a7c3f;font-weight:bold;line-height:1;">&#9632;&#9632;<br><span style="font-size:13pt;color:#1a3060;">&#8962;</span></span></div><div class="logo-text-cell"><div class="logo-name">CHABRIN<br>AGENCIES<span style="font-size:8pt;">&#9644;</span><br>LTD</div><div class="logo-reg">Registered Property Management &amp; Consultants</div></div></div></div>
        <div class="header-right">NACICO PLAZA, LANDHIES ROAD<br>5<sup>TH</sup> FLOOR &ndash; ROOM 517<br>P.O. Box 16659 &ndash; 00620<br>NAIROBI<br>CELL : +254-720-854-389<br>MAIL: info@chabrinagencies.co.ke</div>
    </div>
    <hr class="gold-thick"><hr class="gold-thin">

    <ol class="main-list" start="3">
        <li><strong>The landlord covenant with the tenant as follows:</strong>
            <ol class="alpha-list">
                <li>To permit the tenant to peacefully hold and enjoy the said premises during the said term
                    without any interruption by the landlord or any person or agents rightfully claiming under
                    or in trust of the landlord, so long as the tenant pays the rent hereby reserved and
                    performs and observes the several covenants and the conditions herein contained.</li>
                <li>To keep the walls, roof and structure of the premises in good and tenantable state of
                    repair and maintenance.</li>
                <li>To keep adequately lighted, cleaned and in good state the repair and condition the
                    entrance halls and all common area of the said premises.</li>
            </ol>
        </li>
        <li>The landlord shall have a right of re-entry and possession if any rent shall not have been paid
            as agreed or on breach or non-observance by the tenant of any covenant herein contained or
            on bankruptcy or composition with creditors or suffering distress or execution. In that event this
            agreement shall stand terminated automatically, without prejudice to landlord&rsquo;s rights under
            this agreement.</li>
        <li>In case of the premises being required for statutory duties or re-construction the landlord shall
            give the tenant notice not more than six months from the date of service.</li>
        <li>Any party hereto wishing to terminate the tenancy created hereby shall serve upon the other
            party written notice of his/her intention to do so and such notice shall be for a period of not
            less than one(1) calendar month.</li>
        <li>Service under this lease shall be sufficiently affected if sent to any party and registered post
            or left at the party&rsquo;s last known address in Kenya. The date of the posted service is the date
            when the notice is posted as indicated by postal stamp on the envelope or the Lessee notice
            when received by the Lessor.</li>
    </ol>

</div>

{{-- ═══════════ PAGE 5 — SCHEDULE + SIGNATURES ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap">
        <div class="header-left"><div class="logo-inner"><div class="logo-icon-cell"><span style="font-size:19pt;color:#4a7c3f;font-weight:bold;line-height:1;">&#9632;&#9632;<br><span style="font-size:13pt;color:#1a3060;">&#8962;</span></span></div><div class="logo-text-cell"><div class="logo-name">CHABRIN<br>AGENCIES<span style="font-size:8pt;">&#9644;</span><br>LTD</div><div class="logo-reg">Registered Property Management &amp; Consultants</div></div></div></div>
        <div class="header-right">NACICO PLAZA, LANDHIES ROAD<br>5<sup>TH</sup> FLOOR &ndash; ROOM 517<br>P.O. Box 16659 &ndash; 00620<br>NAIROBI<br>CELL : +254-720-854-389<br>MAIL: info@chabrinagencies.co.ke</div>
    </div>
    <hr class="gold-thick"><hr class="gold-thin">

    <div class="schedule-title">THE SCHEDULE</div>

    <ol class="sched">
        <li>The date of commencement of the lease is
            <span class="uline" style="min-width:60px;">{{ $lease->start_date ? $lease->start_date->format('d') : '' }}</span>/
            <span class="uline" style="min-width:100px;">{{ $lease->start_date ? $lease->start_date->format('F') : '' }}</span>/
            <span class="uline" style="min-width:60px;">{{ $lease->start_date ? $lease->start_date->format('Y') : '' }}</span>
        </li>
        <li>The term of tenancy is {{ $lease->lease_term_months ? ($lease->lease_term_months >= 12 ? floor($lease->lease_term_months/12).' year(s)' : $lease->lease_term_months.' month(s)') : 'periodic tenancy' }}.</li>
        <li>The monthly rent is Kshs <span class="uline" style="min-width:160px;">{{ number_format($lease->monthly_rent, 2) }}</span></li>
        <li>The rent shall be reviewed after each calendar year to the market rates or to such a reasonable
            figure and the tenant shall henceforth pay the reviewed rent.</li>
        <li>The rent is payable monthly in advance by 1<sup>st</sup> day and the deadline will be 5<sup>th</sup> day of each
            calendar month.</li>
        <li>The premise is designed as Plot No.
            <span class="uline" style="min-width:200px;">{{ $property->plot_number ?? $property->reference_number ?? '' }}</span>
        </li>
    </ol>

    {{-- SIGNATURES --}}
    <p class="body" style="margin-top:14px;">
        <strong>IN WITNESS WHEREOF</strong> this agreement was duly executed by the parties hereto the day and year
        first above written.
    </p>

    {{-- Managing Agents --}}
    <div class="sig-row">
        <div class="sig-lbl">
            <strong>Signed</strong> by the Managing Agents )&nbsp;&nbsp;&nbsp;<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(For the Landlord)<br>
            The said )
        </div>
        <div class="sig-box">
            <span class="sig-line-el">&nbsp;</span>
        </div>
    </div>

    <div class="sig-row">
        <div class="sig-lbl">In the presence of )</div>
        <div class="sig-box">
            <span class="sig-line-el">&nbsp;</span>
        </div>
    </div>

    <br>

    {{-- Tenant --}}
    <div class="sig-row">
        <div class="sig-lbl">
            Signed by the tenant )<br>
            The said ID NO. {{ $tenant->national_id ?? $tenant->id_number ?? '' }} )
        </div>
        <div class="sig-box">
            @if(!empty($signatureImagePath) && file_exists($signatureImagePath))
                <img class="sig-img" src="{{ $signatureImagePath }}" alt="Tenant Signature">
                <div class="sig-meta">
                    Digitally signed: {{ $digitalSignature?->created_at?->format('d M Y, h:i A') }}<br>
                    IP: {{ $digitalSignature?->ip_address ?? 'N/A' }}
                </div>
            @else
                <span class="sig-line-el">&nbsp;</span>
            @endif
        </div>
    </div>

    @if(!empty($signatureImagePath) && file_exists($signatureImagePath))
        {{-- DIGITAL: Electronic Execution Record replaces witness line --}}
        <div class="exec-record">
            <b>ELECTRONIC EXECUTION RECORD</b>
            This lease was executed digitally. The tenant&rsquo;s identity was verified via One-Time
            Password (OTP) sent to their registered mobile number prior to signing. This audit trail
            constitutes the record of execution in accordance with the Business Laws (Amendment)
            Act No. 1 of 2020:<br><br>
            <strong>Signing timestamp:</strong> {{ $digitalSignature?->created_at?->format('d M Y, h:i:s A') }}<br>
            <strong>IP Address:</strong> {{ $digitalSignature?->ip_address ?? 'N/A' }}<br>
            <strong>Verification:</strong> OTP &ndash; registered mobile number<br>
            <strong>Lease reference:</strong> {{ $lease->reference_number }}
        </div>
    @else
        {{-- PHYSICAL: traditional witness line --}}
        <div class="sig-row">
            <div class="sig-lbl">In the presence of: )</div>
            <div class="sig-box"><span class="sig-line-el">&nbsp;</span></div>
        </div>
    @endif

</div>

</body>
</html>
