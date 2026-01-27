<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configure OTP generation and verification settings.
    |
    */

    'otp' => [
        // OTP code length (6 digits recommended for security)
        'code_length' => env('LEASE_OTP_LENGTH', 6),

        // OTP expiry time in minutes
        'expiry_minutes' => env('LEASE_OTP_EXPIRY', 10),

        // Maximum OTP attempts per hour per lease
        'max_attempts_per_hour' => env('LEASE_OTP_MAX_ATTEMPTS', 3),

        // Maximum verification attempts before OTP is invalidated
        'max_verification_attempts' => env('LEASE_OTP_MAX_VERIFY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Digital Signing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure digital signing link settings.
    |
    */

    'signing' => [
        // Signing link expiry time in hours
        'link_expiry_hours' => env('LEASE_SIGNING_LINK_EXPIRY', 72),

        // Notification methods: 'email', 'sms', 'both'
        'default_notification_method' => env('LEASE_SIGNING_NOTIFICATION', 'both'),
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Renewal Configuration
    |--------------------------------------------------------------------------
    |
    | Configure lease renewal settings.
    |
    */

    'renewal' => [
        // Default rent escalation rate for renewals (10% = 0.10)
        'default_escalation_rate' => env('LEASE_RENEWAL_ESCALATION_RATE', 0.10),

        // Days before expiry to generate renewal offer
        'offer_days_before_expiry' => env('LEASE_RENEWAL_OFFER_DAYS', 60),

        // Days the tenant has to accept/decline the renewal offer
        'acceptance_deadline_days' => env('LEASE_RENEWAL_ACCEPTANCE_DAYS', 30),

        // Default renewal term in months
        'default_term_months' => env('LEASE_RENEWAL_TERM_MONTHS', 12),

        // Deposit escalation rate (may differ from rent escalation)
        'deposit_escalation_rate' => env('LEASE_DEPOSIT_ESCALATION_RATE', 0.10),

        // Maximum allowed escalation percentage (safety cap)
        'max_escalation_percentage' => env('LEASE_MAX_ESCALATION', 20),

        // Expiry alert thresholds (days before expiry)
        'alert_thresholds' => [90, 60, 30],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lawyer Workflow Configuration
    |--------------------------------------------------------------------------
    |
    | Configure lawyer review workflow settings.
    |
    */

    'lawyer' => [
        // Expected turnaround time in days
        'expected_turnaround_days' => env('LEASE_LAWYER_TURNAROUND', 7),

        // Send reminder after this many days
        'reminder_after_days' => env('LEASE_LAWYER_REMINDER', 5),
    ],

];
