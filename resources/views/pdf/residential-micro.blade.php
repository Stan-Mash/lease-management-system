<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tenancy Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #000;
            margin: 0; padding: 0;
        }
        .page { padding: 16mm 18mm 14mm 18mm; }

        /* HEADER */
        .header-wrap { display: table; width: 100%; margin-bottom: 2px; }
        .header-left  { display: table-cell; width: 38%; vertical-align: top; }
        .header-right {
            display: table-cell; width: 62%; text-align: right;
            vertical-align: top; font-size: 9pt; font-weight: bold; color: #b8880c;
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

        /* QR + REF */
        .qr-fixed { position: fixed; top: 10mm; right: 10mm; width: 24mm; height: 24mm; }
        .qr-fixed svg { width: 24mm; height: 24mm; }
        .ref-tag { position: fixed; top: 36mm; right: 8mm; font-size: 6pt; color: #777; text-align: right; font-family: monospace; width: 28mm; }

        /* TITLE */
        .doc-title {
            text-align: center; font-size: 12.5pt;
            font-weight: bold; text-decoration: underline;
            margin: 10px 0 12px 0;
        }

        /* FIELDS */
        .uline        { border-bottom: 1px dotted #000; display: inline-block; min-width: 100px; }
        .uline-wide   { border-bottom: 1px dotted #000; display: inline-block; min-width: 240px; }
        .uline-full   { border-bottom: 1px dotted #000; display: inline-block; width: 97%; }
        .field-row    { margin-bottom: 5px; }

        /* INTRO */
        p.body { text-align: justify; margin: 6px 0; }

        /* CONDITIONS */
        .conditions-head { font-weight: bold; text-decoration: underline; margin: 10px 0 6px 0; }
        ol.cond { margin: 0; padding-left: 22px; }
        ol.cond > li { margin-bottom: 7px; text-align: justify; }

        /* SIGNATURES */
        .sig-section { margin-top: 18px; }
        .sig-witness-line { border-bottom: 1px solid #000; display: inline-block; min-width: 220px; }
        .sig-img  { max-width: 220px; max-height: 75px; border-bottom: 1px solid #000; display: block; margin-top:4px; }
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

@php $qr = \App\Services\QRCodeService::generateForLease($lease, false); @endphp
<div class="qr-fixed">{!! $qr['svg'] !!}</div>
<div class="ref-tag">Ref: {{ $lease->reference_number }}<br>@if($lease->serial_number)S/N: {{ $lease->serial_number }}@endif</div>
<div class="watermark">CHABRIN<br>AGENCIES<br>LTD</div>

{{-- ═══════════ PAGE 1 ═══════════ --}}
<div class="page">

    <div class="header-wrap">
        <div class="header-left">
            <div class="logo-inner">
                <div class="logo-icon-cell"><span style="font-size:19pt;color:#4a7c3f;font-weight:bold;line-height:1;">&#9632;&#9632;<br><span style="font-size:13pt;color:#1a3060;">&#8962;</span></span></div>
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

    <div class="doc-title">TENANCY AGREEMENT</div>

    <p class="body">
        <strong>THIS AGREEMENT</strong> is made this
        <span class="uline" style="min-width:34px;">{{ $lease->created_at ? $lease->created_at->format('j') : '&hellip;&hellip;&hellip;&hellip;' }}</span>
        day of <span class="uline" style="min-width:130px;">{{ $lease->created_at ? $lease->created_at->format('F') : '&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;' }}</span>
        20<span class="uline" style="min-width:34px;">{{ $lease->created_at ? $lease->created_at->format('y') : '___' }}</span>
        between <span class="uline" style="min-width:200px;">Chabrin Agencies Ltd</span>
        &ldquo;The duly appointed Managing Agent&rdquo; of the said property and:
    </p>

    <div class="field-row" style="margin-top:8px;">
        <strong>TENANT&rsquo;S NAME:</strong>
        <span class="uline-full">{{ $tenant->names ?? $tenant->full_name ?? '' }}</span>
    </div>
    <div class="field-row">
        <strong>ID</strong>
        <span class="uline" style="min-width:160px;">{{ $tenant->national_id ?? $tenant->id_number ?? '' }}</span>
        <strong>(Attach copy) ADDRESS:</strong>
        <span class="uline" style="min-width:180px;">{{ $tenant->address ?? '' }}</span>
    </div>
    <div class="field-row">
        <strong>TEL:</strong>
        <span class="uline" style="min-width:140px;">{{ $tenant->mobile_number ?? $tenant->phone_number ?? '' }}</span>
        <strong>PLACE OF WORK:</strong>
        <span class="uline" style="min-width:160px;">{{ $tenant->employer ?? $tenant->place_of_work ?? '' }}</span>
    </div>
    <div class="field-row">
        <strong>NEXT OF KIN:</strong>
        <span class="uline" style="min-width:200px;">{{ $tenant->next_of_kin ?? '' }}</span>
        <strong>TEL:</strong>
        <span class="uline" style="min-width:130px;">{{ $tenant->next_of_kin_phone ?? '' }}</span>
    </div>
    <div class="field-row">
        <strong>PROPERTY NAME:</strong>
        <span class="uline" style="min-width:180px;">{{ $property->property_name ?? $property->name ?? '' }}</span>
        <strong>ROOM NO:</strong>
        <span class="uline" style="min-width:100px;">{{ $unit->unit_number ?? '' }}</span>
    </div>
    <div class="field-row">
        <strong>HOUSE DEPOSIT PAID:</strong>
        <span class="uline" style="min-width:100px;">{{ number_format($lease->deposit_amount, 2) }}</span>
        <strong>RECEIPT NO.</strong>
        <span class="uline" style="min-width:100px;">&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;</span>
        <strong>DATE:</strong>
        <span class="uline" style="min-width:100px;">&hellip;&hellip;/&hellip;&hellip;&hellip;../&hellip;&hellip;&hellip;&hellip;</span>
    </div>

    <p class="body" style="margin-top:10px;"><em><strong>WHERE IT IS AGREED BETWEEN the parties as follows:-</strong></em></p>

    <p class="conditions-head">CONDITIONS</p>

    <ol class="cond">
        <li>Rent is <strong>STRICTLY</strong> payable on or before the 1<sup>st</sup> day of the month and the deadline will
            be on the <strong><u>5<sup>th</sup></u></strong> of every month during the tenancy period.</li>

        <li>An equivalent of one-month rent will be paid as deposit and a Kshs.
            <span class="uline" style="min-width:90px;">&nbsp;</span>
            electricity and water deposits payable to Chabrin Agencies Ltd bank accounts. The rent deposit sum
            is refundable at the termination of this tenancy with proper one (1) calendar months&rsquo; written
            notice. The said sum may be utilized to defray any outstanding conservancy charges, damages or
            expenses which would be at all material times may be payable by the tenant within the tenancy
            period and such, the deposit should <strong>NEVER</strong> be used as the last months&rsquo; rent payment.
            Refunds done on <strong><u>25<sup>th</sup>/26<sup>th</sup> of the month</u></strong> upon following the laid down procedures.</li>

        <li>Either party can terminate this agreement by giving a one (1) calendar Months&rsquo; notice in writing.</li>

        <li>The property owner will only allow established occupants before renting a out a unit in the
            premise.</li>

        <li>To permit the Landlord, his agents, workmen or servants at all reasonable times on notice from
            the landlord whether oral or written to enter upon the said premises or part thereof and execute
            structural or other repairs to the building.</li>

        <li>No reckless use of water will be tolerated. Only authorized occupants will enjoy this facility.</li>
    </ol>

</div>

{{-- ═══════════ PAGE 2 ═══════════ --}}
<div class="page-break"></div>
<div class="page">

    <div class="header-wrap">
        <div class="header-left">
            <div class="logo-inner">
                <div class="logo-icon-cell"><span style="font-size:19pt;color:#4a7c3f;font-weight:bold;line-height:1;">&#9632;&#9632;<br><span style="font-size:13pt;color:#1a3060;">&#8962;</span></span></div>
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

    <ol class="cond" start="7">
        <li>Anti-social activities likely to inconvenience other tenants like loud music or any other
            unnecessary noise <strong>SHALL NOT</strong> be tolerated and as such, the said behavior shall be deemed
            as breach of this agreement that shall form the basis of terminating the tenancy without further
            reference to the tenant.</li>

        <li>It will be the responsibility of every tenant to keep the premises clean.</li>

        <li>Sources of energy such as firewood, charcoal, open lamps or any other smoking instrument
            should not be used in the premises.</li>

        <li>To use the premises for private residential purposes only and not carry any form of business or
            use them as a boarding house or any other unauthorized purpose without the consent of the
            Landlord in writing.</li>

        <li>Not to make or permit to made any alterations in or additions to the said premises nor to erect
            any fixtures therein nor drive any nails, screws, bolts or wedges in the floors, walls or ceilings
            thereof without the consent in writing of the Landlord first hand and obtained(which consent shall
            not unreasonably withheld).</li>

        <li>Not to sublet or let out the space apportioned under the lease. Breach of this clause will lead to
            immediate termination of the running lease.</li>

        <li>Deposit should be updated from time to time as the house rent is adjusted.</li>

        <li>In the event of failure to pay the said rents or any other sum due under this lease within seven
            (7) days of the due date whether formally demanded or not the Landlord/Agent may take
            necessary action or sending auctioneers to the lessee to recover the said sum due as to costs
            and any incidentals to be borne by the lessee.</li>

        <li>The tenant/lessee shall insure his personal and household belongings and indemnify the landlord
            against any action claim or demand arising from any loss, damage, theft or injury to the tenant
            or tenant&rsquo;s family, licensee, invitees or servants.</li>

        <li>No extension of this agreement shall be implied even though the tenant should continue to be
            in possession of the said premises after the expiration of the said term.</li>

        <li>Any delay by the lessor in exercising any rights hereunder shall not be deemed to be a waiver
            of such rights in any way.</li>
    </ol>

    <p class="body" style="margin-top:12px;">
        <em><strong>IN WITNESS WHEREOF</strong></em> the parties hereto set their hands and seal the day and the year herein
        before mentioned.
    </p>

    {{-- SIGNATURES --}}
    <div class="sig-section">

        {{-- Managing Agent --}}
        <p style="margin-bottom:4px;">
            SIGNED: <strong>MANAGING AGENT</strong>
            <span class="sig-witness-line">&nbsp;</span>
            Date <span class="uline" style="min-width:100px;">&hellip;&hellip;/&hellip;&hellip;&hellip;&hellip;./&hellip;&hellip;&hellip;&hellip;</span>
        </p>

        <br>

        {{-- Tenant --}}
        <p style="margin-bottom:4px;"><strong>SIGNED: TENANT</strong></p>
        <p style="margin-bottom:4px;">
            Name <span class="uline" style="min-width:180px;">{{ $tenant->names ?? $tenant->full_name ?? '' }}</span>
        </p>

        @if(!empty($signatureImagePath) && file_exists($signatureImagePath))
            <img class="sig-img" src="{{ $signatureImagePath }}" alt="Tenant Signature">
            <p class="sig-meta">
                Digitally signed: {{ $digitalSignature?->created_at?->format('d M Y, h:i A') }}<br>
                IP: {{ $digitalSignature?->ip_address ?? 'N/A' }}
            </p>
            <div class="exec-record" style="margin-top:10px;">
                <b>ELECTRONIC EXECUTION RECORD</b>
                This agreement was executed digitally. The tenant&rsquo;s identity was verified via One-Time
                Password (OTP) sent to their registered mobile number prior to signing. This audit trail
                constitutes the record of execution in accordance with the Business Laws (Amendment)
                Act No. 1 of 2020:<br><br>
                <strong>Signing timestamp:</strong> {{ $digitalSignature?->created_at?->format('d M Y, h:i:s A') }}<br>
                <strong>IP Address:</strong> {{ $digitalSignature?->ip_address ?? 'N/A' }}<br>
                <strong>Verification:</strong> OTP &ndash; registered mobile number<br>
                <strong>Lease reference:</strong> {{ $lease->reference_number }}
            </div>
        @else
            <p style="margin-bottom:4px;">
                Signature <span class="sig-witness-line">&nbsp;</span>
                Date <span class="uline" style="min-width:100px;">&hellip;&hellip;/&hellip;&hellip;&hellip;&hellip;./&hellip;&hellip;&hellip;&hellip;</span>
            </p>
        @endif

    </div>

</div>

</body>
</html>
