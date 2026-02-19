<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 20px;
            position: relative;
        }
        .header h1 {
            color: #1a365d;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .qr-code-container {
            position: absolute;
            top: 0;
            right: 0;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        .qr-code-container img {
            width: 120px;
            height: 120px;
            display: block;
            margin: 0 auto 5px auto;
        }
        .qr-code-container .serial {
            font-size: 9px;
            font-weight: bold;
            color: #1a365d;
            font-family: monospace;
        }
        .qr-code-container .verify-text {
            font-size: 7px;
            color: #666;
            margin-top: 2px;
        }
        .reference {
            text-align: right;
            font-size: 11px;
            color: #666;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a365d;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .label {
            display: table-cell;
            width: 35%;
            font-weight: bold;
            color: #555;
        }
        .value {
            display: table-cell;
            width: 65%;
        }
        .terms {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .terms h3 {
            margin-top: 0;
            color: #1a365d;
        }
        .terms ol {
            margin: 0;
            padding-left: 20px;
        }
        .terms li {
            margin-bottom: 10px;
        }
        .signatures {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            padding: 20px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CHABRIN AGENCIES LTD</h1>
        <p>Property Management Services</p>

        @if(config('lease.qr_codes.enabled', true) && ($lease->qr_code_data || $lease->serial_number))
        <div class="qr-code-container">
            @php
                use App\Services\QRCodeService;
                $qrDataUri = QRCodeService::getBase64DataUri($lease);
            @endphp
            <img src="{{ $qrDataUri }}" alt="QR Code">
            @if($lease->serial_number)
            <div class="serial">{{ $lease->serial_number }}</div>
            @endif
            <div class="verify-text">Scan to verify authenticity</div>
        </div>
        @endif
    </div>

    <div class="reference">
        @if($lease->serial_number)
        <strong>Serial Number:</strong> {{ $lease->serial_number }}<br>
        @endif
        <strong>Reference:</strong> {{ $lease->reference_number }}<br>
        <strong>Date:</strong> {{ now()->format('d/m/Y') }}
    </div>

    <h2 style="text-align: center; color: #1a365d;">TENANCY AGREEMENT</h2>

    <div class="section">
        <div class="section-title">PARTIES</div>
        <div class="row">
            <span class="label">Landlord/Agent:</span>
            <span class="value">Chabrin Agencies Ltd</span>
        </div>
        <div class="row">
            <span class="label">Tenant Name:</span>
            <span class="value">{{ $lease->tenant->full_name ?? $lease->tenant->name ?? 'N/A' }}</span>
        </div>
        <div class="row">
            <span class="label">ID Number:</span>
            <span class="value">{{ $lease->tenant->id_number ?? 'N/A' }}</span>
        </div>
        <div class="row">
            <span class="label">Phone:</span>
            <span class="value">{{ $lease->tenant->phone ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">PROPERTY DETAILS</div>
        <div class="row">
            <span class="label">Property:</span>
            <span class="value">{{ $lease->property->name ?? 'N/A' }}</span>
        </div>
        <div class="row">
            <span class="label">Unit Number:</span>
            <span class="value">{{ $lease->unit->unit_number ?? 'N/A' }}</span>
        </div>
        <div class="row">
            <span class="label">Zone:</span>
            <span class="value">{{ $lease->zone ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">FINANCIAL TERMS</div>
        <div class="row">
            <span class="label">Monthly Rent:</span>
            <span class="value">KES {{ number_format($lease->monthly_rent, 2) }}</span>
        </div>
        <div class="row">
            <span class="label">Security Deposit:</span>
            <span class="value">KES {{ number_format($lease->deposit_amount, 2) }}</span>
        </div>
        <div class="row">
            <span class="label">Payment Due:</span>
            <span class="value">1st of each month</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">LEASE PERIOD</div>
        <div class="row">
            <span class="label">Start Date:</span>
            <span class="value">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : 'N/A' }}</span>
        </div>
        <div class="row">
            <span class="label">End Date:</span>
            <span class="value">{{ $lease->end_date ? $lease->end_date->format('d/m/Y') : 'Periodic Tenancy' }}</span>
        </div>
        <div class="row">
            <span class="label">Lease Type:</span>
            <span class="value">{{ ucfirst(str_replace('_', ' ', $lease->lease_type ?? 'N/A')) }}</span>
        </div>
    </div>

    <div class="terms">
        <h3>TERMS AND CONDITIONS</h3>
        <ol>
            <li>Rent is due on or before the 1st day of each month.</li>
            <li>A late payment fee will be charged for payments received after the 5th.</li>
            <li>The tenant shall maintain the premises in good condition.</li>
            <li>No alterations to the property without written consent from the landlord.</li>
            <li>The tenant shall not sublet without prior written approval.</li>
            <li>Either party may terminate with one month's written notice.</li>
            <li>The security deposit will be refunded upon satisfactory inspection at move-out.</li>
        </ol>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <strong>Landlord/Agent</strong><br>
                Chabrin Agencies Ltd
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>Tenant</strong><br>
                {{ $lease->tenant->names ?? $lease->tenant->full_name ?? 'N/A' }}
                @if (!empty($digitalSignature) && !empty($digitalSignature->signature_data))
                    <br>
                    <img src="{{ $digitalSignature->data_uri }}"
                         style="max-width:180px; max-height:70px; margin-top:6px; border-bottom:1px solid #000;"
                         alt="Tenant Signature">
                    <br>
                    <span style="font-size:9pt; color:#555;">
                        Digitally signed: {{ $digitalSignature->created_at?->format('d M Y, h:i A') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This document was generated by Chabrin Lease Management System</p>
        <p>
            @if($lease->serial_number)
            Serial: {{ $lease->serial_number }} |
            @endif
            Reference: {{ $lease->reference_number }} | Generated: {{ now()->format('d/m/Y H:i') }}
        </p>
        @if($lease->verification_url && config('lease.qr_codes.enabled', true))
        <p style="margin-top: 10px; font-size: 9px; color: #666;">
            Verify this document at: {{ $lease->verification_url }}
        </p>
        @endif
    </div>
</body>
</html>
