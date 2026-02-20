<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Commercial Lease Agreement - {{ $lease->reference_number }}</title>
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

        /* COVER PAGE */
        .cover {
            padding: 0; width: 100%; height: 297mm;
            position: relative; background: #fff;
            page-break-after: always;
        }
        .cover-dark-top {
            position: absolute; top: 0; right: 0;
            width: 55%; height: 38%; background: #3d3d3d;
        }
        .cover-green-mid {
            position: absolute; top: 20%; left: 0;
            width: 48%; height: 32%; background: #8bc34a;
        }
        .cover-photo {
            position: absolute; bottom: 14%; left: 0;
            width: 52%; height: 44%;
            background: linear-gradient(135deg, #7aaf40 0%, #4a7c3f 100%);
            overflow: hidden;
        }
        .cover-dark-bottom {
            position: absolute; bottom: 0; left: 0;
            width: 48%; height: 14%; background: #3d3d3d;
        }
        .cover-logo {
            position: absolute; top: 6%; right: 5%;
            text-align: right;
        }
        .cover-logo .logo-name { font-size: 13pt; font-weight: bold; color: #1a3060; }
        .cover-logo .logo-sub  { font-size: 7pt; color: #666; }
        .cover-title {
            position: absolute; top: 44%; right: 5%; left: 50%;
            text-align: left;
        }
        .cover-title h1 {
            font-size: 32pt; font-weight: 900; color: #1a1a1a;
            line-height: 1.05; margin: 0;
        }
        .cover-dots {
            position: absolute; bottom: 4%; left: 20%;
            font-size: 28pt; color: #fff; letter-spacing: 12px;
        }

        /* HEADER on inner pages */
        .header-wrap { display: table; width: 100%; margin-bottom: 2px; }
        .header-logo {
            display: table-cell; width: 100%;
            text-align: right; vertical-align: top;
            padding-bottom: 4px;
        }
        .logo-name { font-size: 11pt; font-weight: bold; color: #1a3060; line-height: 1.2; }
        .logo-name .green-sq { color: #8bc34a; }
        /* Green accent bar */
        hr.green-bar { border: none; border-top: 5px solid #8bc34a; margin: 0 0 12px 0; }

        /* WATERMARK */
        .watermark {
            position: fixed; top: 34%; left: 8%; width: 84%;
            text-align: center; font-size: 66pt; font-weight: bold;
            color: rgba(0,0,0,0.04); transform: rotate(-30deg);
            -webkit-transform: rotate(-30deg); z-index: -1;
            letter-spacing: 4px; line-height: 1.1;
        }

        /* QR + REF fixed top-left on inner pages */
        .qr-fixed { position: fixed; top: 10mm; left: 10mm; width: 22mm; height: 22mm; }
        .qr-fixed svg { width: 22mm; height: 22mm; }
        .ref-tag { position: fixed; top: 34mm; left: 8mm; font-size: 6pt; color: #777; font-family: monospace; width: 28mm; }

        /* PAGE NUMBERS */
        .page-num { text-align: center; margin-top: 16px; font-size: 9pt; }

        /* PARTICULARS TABLE */
        .part-table { width: 100%; margin-bottom: 6px; }
        .part-label { font-weight: bold; width: 28%; vertical-align: top; padding: 3px 8px 3px 0; }
        .part-val   { width: 72%; vertical-align: top; padding: 3px 0; text-align: justify; }

        /* FIELDS */
        .uline      { border-bottom: 1px solid #000; display: inline-block; min-width: 120px; }
        .uline-wide { border-bottom: 1px solid #000; display: inline-block; min-width: 220px; }

        /* BODY */
        p.body { text-align: justify; margin: 6px 0; }
        h3.section { font-size: 10.5pt; font-weight: bold; margin: 12px 0 4px 0; }

        /* LISTS */
        ol.alpha { list-style-type: lower-alpha; margin: 4px 0 0 0; padding-left: 24px; }
        ol.alpha > li { margin-bottom: 7px; text-align: justify; }
        ol.roman { list-style-type: lower-roman; margin: 4px 0 0 0; padding-left: 48px; }
        ol.roman > li { margin-bottom: 5px; text-align: justify; }
        ol.num-sched { margin: 0; padding-left: 24px; }
        ol.num-sched > li { margin-bottom: 8px; text-align: justify; }

        /* SECOND SCHEDULE */
        .sched-title {
            text-align: center; font-weight: bold; text-decoration: underline;
            font-size: 11pt; margin: 14px 0 4px 0;
        }
        .sched-sub { text-align: center; font-weight: bold; margin: 0 0 10px 0; }

        /* SIGNATURES */
        .sig-section { margin-top: 16px; }
        .sig-row { display: table; width: 100%; margin-bottom: 10px; }
        .sig-lbl { display: table-cell; width: 50%; vertical-align: top; line-height: 1.8; }
        .sig-box { display: table-cell; width: 50%; vertical-align: top; padding-top: 2px; }
        .sig-line-el { border-bottom: 1px solid #000; width: 100%; display: block; min-height: 20px; }
        .sig-img { max-width: 210px; max-height: 75px; border-bottom: 1px solid #000; display: block; }
        .sig-meta { font-size: 8pt; color: #444; margin-top: 3px; }
        .sig-spacer { height: 32px; display: block; }
        .exec-record {
            border: 1px solid #888; padding: 7px 10px;
            margin-top: 8px; font-size: 8.5pt; background: #fafafa;
        }
        .exec-record b { display: block; font-size: 9pt; margin-bottom: 4px; }
        .notice-box {
            border: 1px solid #000; padding: 8px 12px;
            margin-top: 16px; font-style: italic; font-size: 9.5pt;
        }
        .notice-box ol { margin: 4px 0 0 0; padding-left: 20px; }
        .notice-box li { margin-bottom: 3px; }

        .page-break { page-break-before: always; }
        sup { font-size: 7pt; vertical-align: super; }
    </style>
</head>
<body>

{{-- QR code fixed top-left on inner pages --}}
@php $qr = \App\Services\QRCodeService::generateForLease($lease, false); @endphp
<div class="qr-fixed">{!! $qr['svg'] !!}</div>
<div class="ref-tag">Ref: {{ $lease->reference_number }}<br>@if($lease->serial_number)S/N: {{ $lease->serial_number }}@endif</div>

<div class="watermark">CHABRIN<br>AGENCIES<br>LTD</div>

{{-- ═══════════ COVER PAGE ═══════════ --}}
<div class="cover">
    <div class="cover-dark-top"></div>
    <div class="cover-green-mid"></div>
    <div class="cover-photo">
        <div style="padding:20px; color:#fff; font-size:9pt; opacity:0.3;">&#9632;</div>
    </div>
    <div class="cover-dark-bottom">
        <div class="cover-dots">&#11044; &#11044; &#11044;</div>
    </div>
    <div class="cover-logo">
        <div class="logo-name" style="font-size:14pt; color:#1a3060;">
            CHABRIN<br>
            AGENCIES<span style="color:#8bc34a;">&#9644;&#9644;</span><br>
            LTD
        </div>
    </div>
    <div class="cover-title">
        <h1>COMMERCIAL<br>LEASE<br>AGREEMENT</h1>
    </div>
</div>

{{-- ═══════════ PAGE 1 — PARTICULARS ═══════════ --}}
<div class="page">
    <div class="header-wrap">
        <div class="header-logo">
            <span class="logo-name">CHABRIN<br>AGENCIES<span class="green-sq">&#9644;&#9644;</span><br>LTD</span>
        </div>
    </div>
    <hr class="green-bar">

    <h3 class="section">1.&nbsp;&nbsp;&nbsp;Particulars</h3>

    <table class="part-table">
        <tr>
            <td class="part-label">Date:</td>
            <td class="part-val">This Lease Agreement is dated the
                <span class="uline" style="min-width:34px;">{{ $lease->created_at ? $lease->created_at->format('j') : '___' }}</span>
                day on the month of
                <span class="uline" style="min-width:110px;">{{ $lease->created_at ? $lease->created_at->format('F') : '' }}</span>,
                in the year <span class="uline" style="min-width:60px;">{{ $lease->created_at ? $lease->created_at->format('Y') : '' }}</span>.
            </td>
        </tr>
        <tr>
            <td class="part-label">The Lessor:</td>
            <td class="part-val">
                <span class="uline" style="min-width:280px;">{{ $landlord->name ?? '' }}</span>
                of Post Office Box Number
                <span class="uline" style="min-width:100px;">{{ $landlord->po_box ?? '' }}</span>
                and where the context so admits includes its successors in title and assigns; of the other part.
            </td>
        </tr>
        <tr>
            <td class="part-label">The Lessee:</td>
            <td class="part-val">
                <span class="uline" style="min-width:280px;">{{ $tenant->names ?? $tenant->full_name ?? '' }}</span>
                of ID.No <span class="uline" style="min-width:110px;">{{ $tenant->national_id ?? $tenant->id_number ?? '' }}</span>
                or Company registration no.
                <span class="uline" style="min-width:130px;">{{ $tenant->company_reg ?? '' }}</span>
                and of Post Office Box Number
                <span class="uline" style="min-width:80px;">{{ $tenant->po_box ?? '' }}</span>
                Nairobi, and where the context so admits includes its successors in title and assigns; of the other part.
            </td>
        </tr>
        <tr>
            <td class="part-label">The Building:</td>
            <td class="part-val">The building and improvement on the parcel identified as
                <span class="uline" style="min-width:140px;">{{ $property->lr_number ?? '' }}</span>
                constructed on all that piece of L.R.
                Designed as <span class="uline" style="min-width:160px;">{{ $property->property_name ?? $property->name ?? '' }}</span>.
            </td>
        </tr>
        <tr>
            <td class="part-label">The Term:</td>
            <td class="part-val">
                <span class="uline" style="min-width:40px;">{{ $lease->lease_term_months ? floor($lease->lease_term_months/12) : '5' }}</span>
                years and
                <span class="uline" style="min-width:40px; font-weight:bold;">{{ $lease->lease_term_months ? ($lease->lease_term_months % 12) : '3' }}</span>
                <strong>(Three)</strong> months from
                <span class="uline" style="min-width:30px;">{{ $lease->start_date ? $lease->start_date->format('d') : '____' }}</span>/<span class="uline" style="min-width:30px;">{{ $lease->start_date ? $lease->start_date->format('m') : '_____' }}</span>/<span class="uline" style="min-width:50px;">{{ $lease->start_date ? $lease->start_date->format('Y') : '_____' }}</span>
                To
                <span class="uline" style="min-width:30px;">{{ $lease->end_date ? $lease->end_date->format('d') : '____' }}</span> /
                <span class="uline" style="min-width:30px;">{{ $lease->end_date ? $lease->end_date->format('m') : '____' }}</span> /
                <span class="uline" style="min-width:50px;">{{ $lease->end_date ? $lease->end_date->format('Y') : '____' }}</span>.
            </td>
        </tr>
        <tr>
            <td class="part-label">The Base Rent:</td>
            <td class="part-val">Kshs. <span class="uline" style="min-width:160px;">{{ number_format($lease->monthly_rent, 2) }}</span> per month</td>
        </tr>
        <tr>
            <td class="part-label">Deposit:</td>
            <td class="part-val">Kshs. <span class="uline" style="min-width:120px;">{{ number_format($lease->deposit_amount, 2) }}</span>, to be paid as security bond refundable after giving vacant possession and the same shall not attract any interest.</td>
        </tr>
        <tr>
            <td class="part-label">Other Charges:</td>
            <td class="part-val">Security and any other charges payable by the Lessee either statutory or to the County Government.</td>
        </tr>
        <tr>
            <td class="part-label">Value Added Tax</td>
            <td class="part-val">The rent shall be subjected to Value Added Tax (V.A.T) at a statutory rate of 16%, which translates to Kshs <span class="uline" style="min-width:120px;">{{ number_format($lease->monthly_rent * 0.16, 2) }}</span> to be paid over and above the base rent.</td>
        </tr>
        <tr>
            <td class="part-label">Rent In Advance:</td>
            <td class="part-val">The rent shall be paid in advance on or before the 1<sup>st</sup> day of every month deadline by 5<sup>th</sup> (fifth) of the month due.</td>
        </tr>
    </table>

    <div class="page-num">1</div>
</div>

{{-- ═══════════ PAGE 2 ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap"><div class="header-logo"><span class="logo-name">CHABRIN<br>AGENCIES<span class="green-sq">&#9644;&#9644;</span><br>LTD</span></div></div>
    <hr class="green-bar">

    <table class="part-table">
        <tr>
            <td class="part-label">Rent Review:</td>
            <td class="part-val">Shall be reviewed after each <span class="uline" style="min-width:60px;">&nbsp;</span> year(s) at a guide rate of <span class="uline" style="min-width:50px;">&nbsp;</span> %. The review shall be communicated in writing and in advance offering a period of 3 months&rsquo; notice.</td>
        </tr>
        <tr>
            <td class="part-label">Payment:</td>
            <td class="part-val">All the payments will be done to <strong>Chabrin Agencies Limited</strong></td>
        </tr>
    </table>

    <h3 class="section">2.&nbsp;&nbsp;Grant of Lease</h3>
    <p class="body">The Lessor leases to the Lessee for a period of
        <span class="uline" style="min-width:110px;">{{ $lease->lease_term_months ? $lease->lease_term_months.' months' : '' }}</span>
        from the date of this Agreement all rights, easements, privileges, restrictions, covenants and stipulations of whatever nature affecting the Premises and subject to the payment to the Lessor of:</p>
    <ol class="alpha">
        <li>The rent, which shall be paid on a monthly basis, that is, in advance.</li>
        <li>Rent shall be payable on or before the fifth (5<sup>th</sup>) day of the month when the rent shall be due.</li>
    </ol>

    <h3 class="section">3.&nbsp;&nbsp;The Lessee&rsquo;s Covenants:</h3>
    <p class="body">The Lessee covenants with the Lessor:</p>
    <ol class="alpha">
        <li>To pay the rents on the days prescribed and in the manner set out in this lease, not to exercise any right or claim to withhold rent or any right or claim to legal or equitable set off and if so required by the Lessor, to make such payments to the bank and account which the Lessor may from time to time nominate.</li>
        <li>To pay to the suppliers and to indemnify the Lessor against all charges for electricity, water and other services consumed at or in relation to the allocated Premises.</li>
        <li>To keep the Premises them in clean and habitable condition.</li>
        <li>Not to commit waste nor make any addition or alteration to the Premises <strong><em>without prior written</em></strong> the consent of the Lessor. The Lessee may install internal demountable partitions which shall be approved by the Lessor and removed at the expiration of the Term if required by the Lessor and any damage to the Premises caused by the removal made good.</li>
        <li>Not to neither affix to nor exhibit on the outside of the premises or to any window of the premises or anywhere in the Common parts any name-plate, sign, notice or advertisement except with approval from the Lessor.</li>
    </ol>

    <div class="page-num">2</div>
</div>

{{-- ═══════════ PAGE 3 ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap"><div class="header-logo"><span class="logo-name">CHABRIN<br>AGENCIES<span class="green-sq">&#9644;&#9644;</span><br>LTD</span></div></div>
    <hr class="green-bar">

    <ol class="alpha" style="margin:0; padding-left:24px;" start="6">
        <li>To permit the Lessor to enter on the premises for the purpose of ascertaining that the covenants and conditions of this lease have been observed and performed and to carry out immediately all work required to comply with any notice given by the Lessor to the Lessee specifying any repairs, maintenance, cleaning or decoration which the Lessee has failed to execute in breach of the terms of this lease.</li>
        <li>Not to transfer, charge, sub-let, part with or share possession to the lease and by extension, the premises, to any third party not recognized under this agreement.</li>
        <li>To give notice to the Lessor of any defect in the premises which might give rise to an obligation on the Lessor to do or refrain from doing any act or thing to comply with the provisions of this lease or the duty of care imposed on the Lessor pursuant to the provisions of any law and at all times to display and maintain all notices which the Lessor may from time to time require to be displayed on the Premises.</li>
        <li>At the expiration of the Term, where a renewal has not been approved, to yield up the Premises and in accordance with the terms of this lease and to give up all access and rights to use over the Premises to the Lessor.</li>
        <li>The Lessee shall be responsible for the security of the premises, its assets and staff during the pendency of this lease.</li>
    </ol>

    <h3 class="section">4.&nbsp;&nbsp;The Lessor&rsquo;s Covenants:</h3>
    <ol class="alpha">
        <li>To allow the Lessee peacefully and quietly to hold and enjoy the Premises without any interruption or disturbance from or by the Lessor or any person claiming under or in trust for the Lessor.</li>
        <li>To keep the exterior of the premises in good repair and condition.</li>
        <li>To notify the Lessee in writing, three (3) days in advance of any intended inspection by the Lessor.</li>
        <li>Not to lease, sell, charge or in any way dispose of the premises to any other party during the pendency of this lease.</li>
    </ol>

    <h3 class="section">5.&nbsp;&nbsp;Notice</h3>
    <p class="body">Any notice or communications under or in connection with this lease shall be in writing and shall be delivered personally or by post to the addresses shown above or to such other address as the recipient may have notified to the other party in writing. Proof of posting or dispatch shall be deemed to be proof of receipt.</p>
    <ol class="roman">
        <li>In the case of a letter, on the third business day after posting</li>
        <li>In the case of a telex, cable or facsimile on the business day immediately following the date of despatch.</li>
    </ol>

    <div class="page-num">3</div>
</div>

{{-- ═══════════ PAGE 4 ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap"><div class="header-logo"><span class="logo-name">CHABRIN<br>AGENCIES<span class="green-sq">&#9644;&#9644;</span><br>LTD</span></div></div>
    <hr class="green-bar">

    <h3 class="section">6.&nbsp;&nbsp;Repairs</h3>
    <p class="body">The Lessee accepts this lease is an FRI lease under which all repairs and insurance are the responsibility of the tenant. The tenant will restore the property to its original state.</p>

    <h3 class="section">7.&nbsp;&nbsp;Breach</h3>
    <p class="body">Any party that does not perform its obligations in accordance to the terms set in this agreement shall be deemed to have breached the Agreement.</p>
    <p class="body">Where a breach occurs the non-breaching party has a right to terminate the agreement immediately without notice. The breaching party shall pay the non-breaching party any outstanding amount owing at the time of termination including damages for the said breach.</p>

    <h3 class="section">8.&nbsp;&nbsp;Dispute Resolution</h3>
    <p class="body">Any differences between the parties may be resolved by mutual discussion. However, should there be any breach of the terms of this Agreement the non-breaching party reserves the right to rescind the Agreement and shall be compensated by the breaching party for any damages incurred due to the breach.</p>
    <p class="body">The non-breaching party shall exercise any other rights it has in law when breach occurs.</p>

    <h3 class="section">9.&nbsp;&nbsp;Amendment</h3>
    <p class="body">Review and amendment of this Agreement shall be done by consent of the parties involved and both parties must execute the amendments as proof of consent to the changes made.</p>

    <h3 class="section">10.&nbsp;Headings</h3>
    <p class="body">The headings used herein are purely for convenience purposes and shall not be deemed to constitute part of the Agreement.</p>

    <h3 class="section">11.&nbsp;Governing Law</h3>
    <p class="body">This Agreement shall be governed by and construed pursuant to the laws of Kenya.</p>

    <h3 class="section">12.&nbsp;Captions</h3>
    <p class="body">The captions of the various Articles and Sections of this Lease are for convenience only and do not necessarily define, limit, describe or construe the contents of such Articles or Sections.</p>

    <h3 class="section">13.&nbsp;Severability</h3>
    <p class="body">If any provision of this Lease proves to be illegal, invalid or unenforceable, the remainder of this Lease shall not be affected by such finding, and in lieu of each provision of this Lease that is illegal, invalid or unenforceable, a provision will be added as part of this Lease as similar in terms to such illegal, invalid or unenforceable provision as may be possible and be legal, valid and enforceable.</p>

    <div class="page-num">4</div>
</div>

{{-- ═══════════ PAGE 5 — SECOND SCHEDULE ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap"><div class="header-logo"><span class="logo-name">CHABRIN<br>AGENCIES<span class="green-sq">&#9644;&#9644;</span><br>LTD</span></div></div>
    <hr class="green-bar">

    <h3 class="section">14.&nbsp;Entire Agreement; Amendment</h3>
    <p class="body">This Lease contains the entire agreement between Lessor and Lessee. No amendment, alteration, modification of, or addition to the Lease will be valid or binding unless expressed in writing and signed by Lessor and Lessee.</p>

    <h3 class="section">15.&nbsp;Legal Fees</h3>
    <p class="body">The cost of and incidental of preparation and completion of the Lease including stamp duty and registration fee shall be borne and paid by the Lessee.</p>

    <div class="sched-title">SECOND SCHEDULE</div>
    <div class="sched-sub">Rights granted</div>

    <ol class="num-sched">
        <li>The right for the Lessee and all persons expressly or by implication authorised by the Lessee in common with the Lessor and all other persons having a like right to use the Common Parts for all proper purposes in connection with the use and enjoyment of the Premises.</li>
        <li>The right for the Lessee and all persons expressly or by implication authorised by the Lessee in common with all other Lessees on the same floor of the Building as the Premises having a like right to use the shared parts for all proper purposes in connection with the use and enjoyment of the premises.</li>
        <li>The right in common with the Lessor and all other persons having a like right, to the free and uninterrupted passage and running subject to temporary interruption for repair, alteration or replacement of water, sewage, electricity, telephone and other services or supplies to and from the premises in and through the pipes which are laid in on over or under other parts of the building and which serve the premises.</li>
        <li>The right of support and protection for the benefit of the premises as is now enjoyed from all other parts of the building.</li>
        <li>The right to display in the reception area of the Building and immediately outside the entrance to the premises a name-plate or sign in a position and of a size and type specified by the Lessor showing the Lessee&rsquo;s name and other details approved by the Lessor.</li>
        <li>The right in cases of emergency only for the Lessee and all persons expressly or by implication authorised by the Lessee, to break and enter any Lettable Area and to have a right of way over such Lettable Area in order to gain access to any fire escapes of the Building.</li>
    </ol>

    <div class="page-num">5</div>
</div>

{{-- ═══════════ PAGE 6 — SIGNATURES ═══════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="header-wrap"><div class="header-logo"><span class="logo-name">CHABRIN<br>AGENCIES<span class="green-sq">&#9644;&#9644;</span><br>LTD</span></div></div>
    <hr class="green-bar">

    <p class="body">
        <strong>IN WITNESS</strong> whereof the Parties have hereunto set their respective hands the day and year first
        herein before written.
    </p>

    <div class="sig-section">

        {{-- ── LESSOR / MANAGING AGENTS ── --}}
        <div class="sig-row">
            <div class="sig-lbl">
                SIGNED by the said )<br>
                &nbsp; ) <br>
                <span class="uline" style="min-width:200px;">&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;</span><br>
                <em>(the Lessor/Assigned agents)</em> )
            </div>
            <div class="sig-box">
                <span class="sig-spacer"></span>
                <span class="sig-line-el">&nbsp;</span>
            </div>
        </div>

        <p style="margin-bottom:2px;">Signature</p>
        <br>
        <p style="margin-bottom:2px;">in the presence of :</p>

        <div class="sig-row">
            <div class="sig-lbl">ADVOCATE</div>
            <div class="sig-box"><span class="sig-line-el">&nbsp;</span></div>
        </div>

        <br><br>

        {{-- ── LESSEE / TENANT ── --}}
        <div class="sig-row">
            <div class="sig-lbl">SIGNED by the Lessee )</div>
            <div class="sig-box">
                @if(!empty($signatureImagePath) && file_exists($signatureImagePath))
                    <img class="sig-img" src="{{ $signatureImagePath }}" alt="Tenant Signature">
                    <div class="sig-meta">
                        Digitally signed: {{ $digitalSignature?->created_at?->format('d M Y, h:i A') }}<br>
                        IP: {{ $digitalSignature?->ip_address ?? 'N/A' }}
                    </div>
                @else
                    <span class="sig-spacer"></span>
                    <span class="sig-line-el">&nbsp;</span>
                @endif
            </div>
        </div>

        @if(!empty($signatureImagePath) && file_exists($signatureImagePath))
            {{-- DIGITAL: Electronic Execution Record --}}
            <div class="exec-record">
                <b>ELECTRONIC EXECUTION RECORD</b>
                This lease was executed digitally. The Lessee&rsquo;s identity was verified via One-Time
                Password (OTP) sent to their registered mobile number prior to signing. This audit trail
                constitutes the record of execution in accordance with the Business Laws (Amendment)
                Act No. 1 of 2020:<br><br>
                <strong>Signing timestamp:</strong> {{ $digitalSignature?->created_at?->format('d M Y, h:i:s A') }}<br>
                <strong>IP Address:</strong> {{ $digitalSignature?->ip_address ?? 'N/A' }}<br>
                <strong>Verification:</strong> OTP &ndash; registered mobile number<br>
                <strong>Lease reference:</strong> {{ $lease->reference_number }}
            </div>
        @else
            {{-- PHYSICAL: traditional witness lines --}}
            <div class="sig-row">
                <div class="sig-lbl">in the presence of: )<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
                <div class="sig-box"></div>
            </div>
            <div class="sig-row">
                <div class="sig-lbl">ADVOCATE )</div>
                <div class="sig-box"><span class="uline" style="min-width:220px;">&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;</span></div>
            </div>
        @endif

    </div>

    <div class="notice-box">
        <p style="margin:0 0 4px 0;"><em><strong>As per government policy, you are required to provide the following documents prior to registration of this lease:</strong></em></p>
        <ol>
            <li><em><strong>Copy of business or company registration</strong></em></li>
            <li><em><strong>K.R.A pin certificate of the business/individual</strong></em></li>
            <li><em><strong>Director&rsquo;s or business owner Identification Card</strong></em></li>
        </ol>
    </div>

    <div class="page-num">6</div>
</div>

</body>
</html>
