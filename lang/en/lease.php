<?php

declare(strict_types=1);

/**
 * English strings for tenant-facing lease communications (SMS, email, portal).
 * Variables: :name, :url, :code, :phone, :reference, etc.
 */
return [
    'sms_signing_link' => 'Dear :name, your lease agreement is ready for your digital signature. Click the link to sign: :url (valid for 72 hours). — Chabrin Agencies',
    'sms_otp' => 'Your Chabrin lease verification code is: :code. Valid for 15 minutes. Do not share this code.',
    'sms_link_expired' => 'Your lease signing link has expired. Contact Chabrin Agencies on :phone for a new link.',
    'email_welcome_subject' => 'Your Lease Agreement is Ready — :reference',
    'email_signed_subject' => 'Lease Confirmed — :reference',
    'portal_step1_title' => 'Verify your identity',
    'portal_step2_title' => 'Review and sign',
];
