<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QR Code Configuration
    |--------------------------------------------------------------------------
    |
    | Configure QR code generation and display for lease documents.
    | QR codes provide secure verification of document authenticity.
    |
    */

    'qr_codes' => [
        // Enable QR code display in PDF documents
        'enabled' => env('LEASE_QR_ENABLED', true),

        // Auto-generate QR codes when leases are created/approved
        'auto_generate' => env('LEASE_QR_AUTO_GENERATE', true),

        // QR code size in PDF (pixels)
        'pdf_size' => 200,

        // QR code storage size (pixels)
        'storage_size' => 512,

        // Position in PDF: 'top-right', 'top-left', 'bottom-right', 'bottom-left'
        'position' => 'top-right',
    ],

    /*
    |--------------------------------------------------------------------------
    | Serial Number Configuration
    |--------------------------------------------------------------------------
    |
    | Configure serial number generation for lease documents.
    | Format: PREFIX-YEAR-SEQUENCE (e.g., LSE-2026-0001)
    |
    */

    'serial_number' => [
        // Serial number prefix
        'prefix' => env('LEASE_SERIAL_PREFIX', 'LSE'),

        // Auto-generate serial numbers
        'auto_generate' => env('LEASE_SERIAL_AUTO_GENERATE', true),

        // Reset sequence each year
        'reset_yearly' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Generation
    |--------------------------------------------------------------------------
    |
    | Configure PDF document generation settings.
    |
    */

    'pdf' => [
        // Generate PDF automatically on approval
        'auto_generate_on_approval' => true,

        // Include watermark
        'include_watermark' => false,

        // Paper size: 'A4', 'Letter'
        'paper_size' => 'A4',

        // Orientation: 'portrait', 'landscape'
        'orientation' => 'portrait',
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Settings
    |--------------------------------------------------------------------------
    |
    | Configure public verification settings.
    |
    */

    'verification' => [
        // Enable public verification
        'enabled' => true,

        // Show full lease details on verification
        'show_full_details' => false,

        // Show only basic info (serial, status, validity)
        'show_basic_info' => true,
    ],

];
