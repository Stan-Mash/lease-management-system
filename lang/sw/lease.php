<?php

declare(strict_types=1);

/**
 * Swahili strings for tenant-facing lease communications (SMS, email, portal).
 * Variables: :name, :url, :code, :phone, :reference, etc.
 */
return [
    'sms_signing_link' => 'Habari :name, mkataba :reference unasubiri sahihi yako. Kodi: KES :rent. Saini kwa usalama kupitia kiungo: :url - Chabrin Agencies',
    'sms_otp' => 'Nambari yako ya uthibitisho wa kukodisha Chabrin ni: :code. Ni halali kwa dakika 15. Usishiriki nambari hii.',
    'sms_link_expired' => 'Kiungo cha kusaini kimeisha. Wasiliana: info@chabrinagencies.co.ke au +254720854389. - Chabrin Agencies',
    'email_welcome_subject' => 'Mkataba Wako wa Kukodisha Uko Tayari — :reference',
    'email_signed_subject' => 'Mkataba Umeidhinishwa — :reference',
    'portal_step1_title' => 'Thibitisha utambulisho wako',
    'portal_step2_title' => 'Kagua na saini',
];
