<?php

declare(strict_types=1);

/**
 * English SMS message templates for Chabrin Lease Management System.
 *
 * Variables are denoted with :variable_name syntax.
 * Keep messages concise - SMS has 160 character limit per segment.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | OTP & Verification Messages
    |--------------------------------------------------------------------------
    */
    'otp_message' => 'Your Chabrin verification code is: :code. Valid for :minutes minutes. Ref: :reference. Do not share this code.',

    'otp_resend' => 'New verification code: :code. Valid for :minutes minutes. Ref: :reference.',

    /*
    |--------------------------------------------------------------------------
    | Lease Lifecycle Messages
    |--------------------------------------------------------------------------
    */
    'lease_ready' => 'Your lease :reference is ready for signing. Please check your email or login to complete. - Chabrin Agencies',

    'lease_created' => 'Dear :tenant_name, lease :reference has been created. Await landlord approval. - Chabrin',

    'lease_approved' => 'Good news! Lease :reference has been APPROVED. You will receive the signing link shortly. - Chabrin',

    'lease_rejected' => 'Lease :reference needs revision. Reason: :reason. Contact support for details. - Chabrin',

    'lease_signed' => 'Lease :reference signed successfully! Keep this as your record. Start date: :start_date. - Chabrin',

    'lease_expiring' => 'Reminder: Lease :reference expires on :expiry_date. Contact us to discuss renewal options. - Chabrin',

    'lease_expired' => 'Lease :reference has expired. Please contact Chabrin Agencies to renew or discuss move-out.',

    /*
    |--------------------------------------------------------------------------
    | Signing & Document Messages
    |--------------------------------------------------------------------------
    */
    'signing_link' => 'Sign your lease :reference here: :link. Link expires in :hours hours. - Chabrin Agencies',

    'signing_reminder' => 'Reminder: Please sign lease :reference. Link: :link. Expires in :hours hours. - Chabrin',

    'document_ready' => 'Document :document_name for lease :reference is ready. Login to view. - Chabrin',

    /*
    |--------------------------------------------------------------------------
    | Landlord Messages
    |--------------------------------------------------------------------------
    */
    'approval_request' => 'New lease :reference awaits approval. Tenant: :tenant_name. Rent: Ksh :rent/month. Login to approve.',

    'landlord_signed' => 'Landlord has signed lease :reference. Awaiting tenant signature. - Chabrin',

    /*
    |--------------------------------------------------------------------------
    | Payment & Financial Messages
    |--------------------------------------------------------------------------
    */
    'payment_received' => 'Payment of Ksh :amount received for :reference. Balance: Ksh :balance. Thank you! - Chabrin',

    'payment_reminder' => 'Reminder: Rent of Ksh :amount for :reference is due on :due_date. Pay via M-Pesa Paybill :paybill.',

    'payment_overdue' => 'OVERDUE: Rent of Ksh :amount for :reference was due :due_date. Please pay immediately to avoid penalties.',

    'receipt_sent' => 'Receipt :receipt_no for Ksh :amount sent to your email. Ref: :reference. - Chabrin',

    /*
    |--------------------------------------------------------------------------
    | General Notifications
    |--------------------------------------------------------------------------
    */
    'welcome' => 'Welcome to Chabrin Agencies, :tenant_name! Your tenant profile has been created. Ref: :tenant_id.',

    'profile_updated' => 'Your Chabrin profile has been updated successfully. Contact support if you did not make this change.',

    'support_ticket' => 'Support ticket :ticket_id created. We will respond within 24 hours. - Chabrin Agencies',

    'maintenance_scheduled' => 'Maintenance scheduled for :property on :date. Our team will contact you. Ref: :ticket_id.',
];
