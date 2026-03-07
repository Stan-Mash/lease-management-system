<?php

declare(strict_types=1);

/**
 * English strings for tenant-facing lease communications (SMS, email, portal).
 * Variables: :name, :url, :code, :phone, :reference, etc.
 */
return [
    'sms_signing_link' => 'Dear :name, lease :reference awaits your signature. Rent: KES :rent. Sign securely via the portal: :url - Chabrin Agencies',
    'sms_otp' => 'Your Chabrin lease verification code is: :code. Valid for 15 minutes. Do not share this code.',
    'sms_link_expired' => 'Your lease signing link has expired. Contact us: info@chabrinagencies.co.ke or +254720854389. - Chabrin Agencies',
    'email_welcome_subject' => 'Your Lease Agreement is Ready — :reference',
    'email_signed_subject' => 'Lease Confirmed — :reference',
    'portal_step1_title' => 'Verify your identity',
    'portal_step2_title' => 'Review and sign',
];
