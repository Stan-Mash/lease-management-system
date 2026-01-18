<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Commercial Lease - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 60px 50px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #000;
        }
        .header {
            position: relative;
            margin-bottom: 20px;
            border-bottom: 4px solid #8BC34A;
            padding-bottom: 10px;
        }
        .logo {
            position: absolute;
            right: 0;
            top: 0;
        }
        .logo-text {
            font-size: 14px;
            font-weight: bold;
            color: #1a365d;
        }
        .logo-text .highlight {
            color: #8BC34A;
        }
        .cover-page {
            page-break-after: always;
            text-align: center;
            padding-top: 100px;
        }
        .cover-title {
            font-size: 48px;
            font-weight: bold;
            color: #333;
            margin-top: 200px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .field-row {
            margin-bottom: 8px;
        }
        .field-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .field-value {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
            padding-bottom: 2px;
        }
        .clause {
            margin-bottom: 15px;
            text-align: justify;
        }
        .clause-title {
            font-weight: bold;
        }
        .indent {
            margin-left: 30px;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-block {
            margin-top: 40px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 250px;
            margin-top: 50px;
            display: inline-block;
        }
        .page-number {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
        .notice-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 20px;
            font-style: italic;
        }
        ol {
            margin-left: 0;
            padding-left: 20px;
        }
        ol li {
            margin-bottom: 10px;
            text-align: justify;
        }
        .schedule-title {
            text-align: center;
            text-decoration: underline;
            font-weight: bold;
            margin: 30px 0 20px 0;
        }
        .qr-code {
            position: fixed;
            top: 10px;
            right: 10px;
            width: 100px;
            height: 100px;
            border: 1px solid #ccc;
            padding: 5px;
            background: white;
        }
        .serial-number {
            position: fixed;
            top: 120px;
            right: 10px;
            font-size: 9px;
            color: #666;
            font-family: monospace;
        }
    </style>
</head>
<body>

<!-- QR Code and Serial Number (appears on all pages) -->
@php
    $qrCode = \App\Services\QRCodeService::generateForLease($lease, false);
@endphp
<div class="qr-code">
    {!! $qrCode['svg'] !!}
</div>
<div class="serial-number">
    Serial: {{ $lease->serial_number }}<br>
    Ref: {{ $lease->reference_number }}
</div>

<!-- COVER PAGE -->
<div class="cover-page">
    <div style="text-align: right;">
        <span class="logo-text">CHABRIN<br>AGENCIES<span class="highlight">■■</span><br>LTD</span>
    </div>
    <div class="cover-title">
        COMMERCIAL<br>
        LEASE<br>
        AGREEMENT
    </div>
</div>

<!-- PAGE 1 - PARTICULARS -->
<div class="header">
    <div class="logo">
        <span class="logo-text">CHABRIN<br>AGENCIES<span class="highlight">■■</span><br>LTD</span>
    </div>
</div>

<h3>1. Particulars</h3>

<div class="field-row">
    <span class="field-label">Date:</span>
    This Lease Agreement is dated the <span class="field-value">{{ $lease->created_at ? $lease->created_at->format('jS') : '____' }}</span> day on the month of
    <span class="field-value">{{ $lease->created_at ? $lease->created_at->format('F') : '____________' }}</span>, in the year <span class="field-value">{{ $lease->created_at ? $lease->created_at->format('Y') : '______' }}</span>.
</div>

<div class="field-row">
    <span class="field-label">The Lessor:</span>
    <span class="field-value">{{ $landlord->name ?? '________________________________' }}</span> of Post Office Box Number <span class="field-value">{{ $landlord->po_box ?? '________' }}</span> and where the context so admits includes its successors in title and assigns; of the other part.
</div>

<div class="field-row">
    <span class="field-label">The Lessee:</span>
    <span class="field-value">{{ $tenant->full_name ?? $tenant->name ?? '________________________________' }}</span> of ID.No <span class="field-value">{{ $tenant->id_number ?? '____________' }}</span>
    or Company registration no. <span class="field-value">{{ $tenant->company_reg ?? '____________' }}</span> and of Post Office Box Number <span class="field-value">{{ $tenant->po_box ?? '________' }}</span> Nairobi, and where the context so admits includes its successors in title and assigns; of the other part.
</div>

<div class="field-row">
    <span class="field-label">The Building:</span>
    The building and improvement on the parcel identified as <span class="field-value">{{ $property->lr_number ?? '____________' }}</span>
    constructed on all that piece of L.R. Designed as <span class="field-value">{{ $property->name ?? '____________' }}</span>.
</div>

<div class="field-row">
    <span class="field-label">The Term:</span>
    <span class="field-value">{{ $lease->lease_term_months ? floor($lease->lease_term_months / 12) : '___' }}</span> years and <span class="field-value">{{ $lease->lease_term_months ? ($lease->lease_term_months % 12) : '___' }}</span> months from <span class="field-value">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '___/___/___' }}</span>
    To <span class="field-value">{{ $lease->end_date ? $lease->end_date->format('d/m/Y') : '___/___/___' }}</span>.
</div>

<div class="field-row">
    <span class="field-label">The Base Rent:</span>
    Kshs. <span class="field-value">{{ number_format($lease->monthly_rent, 2) }}</span> per month
</div>

<div class="field-row">
    <span class="field-label">Deposit:</span>
    Kshs. <span class="field-value">{{ number_format($lease->deposit_amount, 2) }}</span>, to be paid as security bond refundable after giving vacant possession and the same shall not attract any interest.
</div>

<div class="field-row">
    <span class="field-label">Other Charges:</span>
    Security and any other charges payable by the Lessee either statutory or to the County Government.
</div>

<div class="field-row">
    <span class="field-label">Value Added Tax:</span>
    The rent shall be subjected to Value Added Tax (V.A.T) at a statutory rate of 16%, which translates to Kshs <span class="field-value">{{ number_format($lease->monthly_rent * 0.16, 2) }}</span> to be paid over and above the base rent.
</div>

<div class="field-row">
    <span class="field-label">Rent In Advance:</span>
    The rent shall be paid in advance on or before the 1<sup>st</sup> day of every month deadline by 5<sup>th</sup> (fifth) of the month due.
</div>

<div class="page-number">1</div>

<!-- PAGE 2 -->
<div style="page-break-before: always;"></div>
<div class="header">
    <div class="logo">
        <span class="logo-text">CHABRIN<br>AGENCIES<span class="highlight">■■</span><br>LTD</span>
    </div>
</div>

<div class="field-row">
    <span class="field-label">Rent Review:</span>
    Shall be reviewed after each <span class="field-value">1</span> year(s) at a guide rate of <span class="field-value">10</span> %. The review shall be communicated in writing and in advance offering a period of 3 months' notice.
</div>

<div class="field-row">
    <span class="field-label">Payment:</span>
    All the payments will be done to <strong>Chabrin Agencies Limited</strong>
</div>

<h3>2. Grant of Lease</h3>
<p class="clause">
    The Lessor leases to the Lessee for a period of <span class="field-value">{{ $lease->lease_term_months ?? '____' }} months</span> from the date of this Agreement all rights, easements, privileges, restrictions, covenants and stipulations of whatever nature affecting the Premises and subject to the payment to the Lessor of:
</p>
<ol type="a">
    <li>The rent, which shall be paid on a monthly basis, that is, in advance.</li>
    <li>Rent shall be payable on or before the fifth (5<sup>th</sup>) day of the month when the rent shall be due.</li>
</ol>

<h3>3. The Lessee's Covenants:</h3>
<p>The Lessee covenants with the Lessor:</p>
<ol type="a">
    <li>To pay the rents on the days prescribed and in the manner set out in this lease, not to exercise any right or claim to withhold rent or any right or claim to legal or equitable set off and if so required by the Lessor, to make such payments to the bank and account which the Lessor may from time to time nominate.</li>
    <li>To pay to the suppliers and to indemnify the Lessor against all charges for electricity, water and other services consumed at or in relation to the allocated Premises.</li>
    <li>To keep the Premises them in clean and habitable condition.</li>
    <li>Not to commit waste nor make any addition or alteration to the Premises <strong>without prior written</strong> the consent of the Lessor. The Lessee may install internal demountable partitions which shall be approved by the Lessor and removed at the expiration of the Term if required by the Lessor and any damage to the Premises caused by the removal made good.</li>
    <li>Not to neither affix to nor exhibit on the outside of the premises or to any window of the premises or anywhere in the Common parts any name-plate, sign, notice or advertisement except with approval from the Lessor.</li>
</ol>

<div class="page-number">2</div>

<!-- PAGE 3 -->
<div style="page-break-before: always;"></div>
<div class="header">
    <div class="logo">
        <span class="logo-text">CHABRIN<br>AGENCIES<span class="highlight">■■</span><br>LTD</span>
    </div>
</div>

<ol type="a" start="6">
    <li>To permit the Lessor to enter on the premises for the purpose of ascertaining that the covenants and conditions of this lease have been observed and performed and to carry out immediately all work required to comply with any notice given by the Lessor to the Lessee specifying any repairs, maintenance, cleaning or decoration which the Lessee has failed to execute in breach of the terms of this lease.</li>
    <li>Not to transfer, charge, sub-let, part with or share possession to the lease and by extension, the premises, to any third party not recognized under this agreement.</li>
    <li>To give notice to the Lessor of any defect in the premises which might give rise to an obligation on the Lessor to do or refrain from doing any act or thing to comply with the provisions of this lease or the duty of care imposed on the Lessor pursuant to the provisions of any law and at all times to display and maintain all notices which the Lessor may from time to time require to be displayed on the Premises.</li>
    <li>At the expiration of the Term, where a renewal has not been approved, to yield up the Premises and in accordance with the terms of this lease and to give up all access and rights to use over the Premises to the Lessor.</li>
    <li>The Lessee shall be responsible for the security of the premises, its assets and staff during the pendency of this lease.</li>
</ol>

<h3>4. The Lessor's Covenants:</h3>
<ol type="a">
    <li>To allow the Lessee peacefully and quietly to hold and enjoy the Premises without any interruption or disturbance from or by the Lessor or any person claiming under or in trust for the Lessor.</li>
    <li>To keep the exterior of the premises in good repair and condition.</li>
    <li>To notify the Lessee in writing, three (3) days in advance of any intended inspection by the Lessor.</li>
    <li>Not to lease, sell, charge or in any way dispose of the premises to any other party during the pendency of this lease.</li>
</ol>

<h3>5. Notice</h3>
<p class="clause">
    Any notice or communications under or in connection with this lease shall be in writing and shall be delivered personally or by post to the addresses shown above or to such other address as the recipient may have notified to the other party in writing. Proof of posting or dispatch shall be deemed to be proof of receipt.
</p>
<ol type="i" class="indent">
    <li>In the case of a letter, on the third business day after posting</li>
    <li>In the case of a telex, cable or facsimile on the business day immediately following the date of despatch.</li>
</ol>

<div class="page-number">3</div>

<!-- PAGE 4 -->
<div style="page-break-before: always;"></div>
<div class="header">
    <div class="logo">
        <span class="logo-text">CHABRIN<br>AGENCIES<span class="highlight">■■</span><br>LTD</span>
    </div>
</div>

<h3>6. Repairs</h3>
<p class="clause">
    The Lessee accepts this lease is an FRI lease under which all repairs and insurance are the responsibility of the tenant. The tenant will restore the property to its original state.
</p>

<h3>7. Breach</h3>
<p class="clause">
    Any party that does not perform its obligations in accordance to the terms set in this agreement shall be deemed to have breached the Agreement.
</p>
<p class="clause">
    Where a breach occurs the non-breaching party has a right to terminate the agreement immediately without notice. The breaching party shall pay the non-breaching party any outstanding amount owing at the time of termination including damages for the said breach.
</p>

<h3>8. Dispute Resolution</h3>
<p class="clause">
    Any differences between the parties may be resolved by mutual discussion. However, should there be any breach of the terms of this Agreement the non-breaching party reserves the right to rescind the Agreement and shall be compensated by the breaching party for any damages incurred due to the breach.
</p>
<p class="clause">
    The non-breaching party shall exercise any other rights it has in law when breach occurs.
</p>

<h3>9. Amendment</h3>
<p class="clause">
    Review and amendment of this Agreement shall be done by consent of the parties involved and both parties must execute the amendments as proof of consent to the changes made.
</p>

<h3>10. Headings</h3>
<p class="clause">
    The headings used herein are purely for convenience purposes and shall not be deemed to constitute part of the Agreement.
</p>

<h3>11. Governing Law</h3>
<p class="clause">
    This Agreement shall be governed by and construed pursuant to the laws of Kenya.
</p>

<h3>12. Captions</h3>
<p class="clause">
    The captions of the various Articles and Sections of this Lease are for convenience only and do not necessarily define, limit, describe or construe the contents of such Articles or Sections.
</p>

<h3>13. Severability</h3>
<p class="clause">
    If any provision of this Lease proves to be illegal, invalid or unenforceable, the remainder of this Lease shall not be affected by such finding, and in lieu of each provision of this Lease that is illegal, invalid or unenforceable, a provision will be added as part of this Lease as similar in terms to such illegal, invalid or unenforceable provision as may be possible and be legal, valid and enforceable.
</p>

<div class="page-number">4</div>

<!-- PAGE 5 -->
<div style="page-break-before: always;"></div>
<div class="header">
    <div class="logo">
        <span class="logo-text">CHABRIN<br>AGENCIES<span class="highlight">■■</span><br>LTD</span>
    </div>
</div>

<h3>14. Entire Agreement; Amendment</h3>
<p class="clause">
    This Lease contains the entire agreement between Lessor and Lessee. No amendment, alteration, modification of, or addition to the Lease will be valid or binding unless expressed in writing and signed by Lessor and Lessee.
</p>

<h3>15. Legal Fees</h3>
<p class="clause">
    The cost of and incidental of preparation and completion of the Lease including stamp duty and registration fee shall be borne and paid by the Lessee.
</p>

<div class="schedule-title">SECOND SCHEDULE</div>
<h4 style="text-align: center;">Rights granted</h4>

<ol>
    <li>The right for the Lessee and all persons expressly or by implication authorised by the Lessee in common with the Lessor and all other persons having a like right to use the Common Parts for all proper purposes in connection with the use and enjoyment of the Premises.</li>
    <li>The right for the Lessee and all persons expressly or by implication authorised by the Lessee in common with all other Lessees on the same floor of the Building as the Premises having a like right to use the shared parts for all proper purposes in connection with the use and enjoyment of the premises.</li>
    <li>The right in common with the Lessor and all other persons having a like right, to the free and uninterrupted passage and running subject to temporary interruption for repair, alteration or replacement of water, sewage, electricity, telephone and other services or supplies to and from the premises in and through the pipes which are laid in on over or under other parts of the building and which serve the premises.</li>
    <li>The right of support and protection for the benefit of the premises as is now enjoyed from all other parts of the building.</li>
    <li>The right to display in the reception area of the Building and immediately outside the entrance to the premises a name-plate or sign in a position and of a size and type specified by the Lessor showing the Lessee's name and other details approved by the Lessor.</li>
    <li>The right in cases of emergency only for the Lessee and all persons expressly or by implication authorised by the Lessee, to break and enter any Lettable Area and to have a right of way over such Lettable Area in order to gain access to any fire escapes of the Building.</li>
</ol>

<div class="page-number">5</div>

<!-- PAGE 6 - SIGNATURES -->
<div style="page-break-before: always;"></div>
<div class="header">
    <div class="logo">
        <span class="logo-text">CHABRIN<br>AGENCIES<span class="highlight">■■</span><br>LTD</span>
    </div>
</div>

<p class="clause">
    <strong>IN WITNESS</strong> whereof the Parties have hereunto set their respective hands the day and year first herein before written.
</p>

<div class="signature-section">
    <p>SIGNED by the said</p>
    <p><span class="signature-line"></span></p>
    <p>(the Lessor/Assigned agents)</p>
    <br>
    <p>Signature</p>
    <br>
    <p>in the presence of:</p>
    <br>
    <p>ADVOCATE</p>
</div>

<div class="signature-section">
    <p>SIGNED by the Lessee</p>
    <br>
    <p>in the presence of:</p>
    <br>
    <p>ADVOCATE <span class="signature-line"></span></p>
</div>

<div class="notice-box">
    <p><strong>As per government policy, you are required to provide the following documents prior to registration of this lease:</strong></p>
    <ol>
        <li>Copy of business or company registration</li>
        <li>K.R.A pin certificate of the business/individual</li>
        <li>Director's or business owner Identification Card</li>
    </ol>
</div>

<div class="page-number">6</div>

</body>
</html>
