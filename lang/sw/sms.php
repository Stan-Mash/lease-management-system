<?php

declare(strict_types=1);

/**
 * Swahili (Kiswahili) SMS message templates for Chabrin Lease Management System.
 *
 * Variables are denoted with :variable_name syntax.
 * Keep messages concise - SMS has 160 character limit per segment.
 *
 * Note: Swahili messages optimized for Kenyan audience comprehension.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | OTP & Verification Messages (Ujumbe wa Uthibitisho)
    |--------------------------------------------------------------------------
    */
    'otp_message' => 'Nambari yako ya uthibitisho ya Chabrin ni: :code. Inatumika kwa dakika :minutes. Kumb: :reference. Usimpe mtu.',

    'otp_resend' => 'Nambari mpya ya uthibitisho: :code. Inatumika kwa dakika :minutes. Kumb: :reference.',

    /*
    |--------------------------------------------------------------------------
    | Lease Lifecycle Messages (Ujumbe wa Mkataba)
    |--------------------------------------------------------------------------
    */
    'lease_ready' => 'Mkataba wako :reference uko tayari kusainiwa. Angalia barua pepe au ingia mtandaoni. - Chabrin Agencies',

    'lease_created' => 'Ndugu :tenant_name, mkataba :reference umeundwa. Subiri idhini ya mmiliki. - Chabrin',

    'lease_approved' => 'Habari njema! Mkataba :reference UMEIDHINISHWA. Utapata kiungo cha kusaini hivi karibuni. - Chabrin',

    'lease_rejected' => 'Mkataba :reference unahitaji marekebisho. Sababu: :reason. Wasiliana na msaada. - Chabrin',

    'lease_signed' => 'Mkataba :reference umesainiwa! Hifadhi hii kama kumbukumbu yako. Tarehe ya kuanza: :start_date. - Chabrin',

    'lease_expiring' => 'Kumbuka: Mkataba :reference unaisha tarehe :expiry_date. Wasiliana nasi kujadili upya. - Chabrin',

    'lease_expired' => 'Mkataba :reference umeisha muda. Tafadhali wasiliana na Chabrin Agencies kuhusu upya au kuondoka.',

    /*
    |--------------------------------------------------------------------------
    | Signing & Document Messages (Kusaini na Hati)
    |--------------------------------------------------------------------------
    */
    'signing_link' => 'Saini mkataba :reference hapa: :link. Kiungo kinaisha baada ya saa :hours. - Chabrin Agencies',

    'signing_reminder' => 'Kumbuka: Tafadhali saini mkataba :reference. Kiungo: :link. Kinaisha saa :hours. - Chabrin',

    'document_ready' => 'Hati :document_name ya mkataba :reference iko tayari. Ingia kuiona. - Chabrin',

    /*
    |--------------------------------------------------------------------------
    | Landlord Messages (Ujumbe kwa Mmiliki)
    |--------------------------------------------------------------------------
    */
    'approval_request' => 'Mkataba mpya :reference unasubiri idhini. Mpangaji: :tenant_name. Kodi: Ksh :rent/mwezi. Ingia kuidhinisha.',

    'landlord_signed' => 'Mmiliki amesaini mkataba :reference. Inasubiri saini ya mpangaji. - Chabrin',

    /*
    |--------------------------------------------------------------------------
    | Payment & Financial Messages (Malipo)
    |--------------------------------------------------------------------------
    */
    'payment_received' => 'Malipo ya Ksh :amount yamepokelewa kwa :reference. Salio: Ksh :balance. Asante! - Chabrin',

    'payment_reminder' => 'Kumbuka: Kodi ya Ksh :amount ya :reference inastahili tarehe :due_date. Lipa kupitia M-Pesa Paybill :paybill.',

    'payment_overdue' => 'IMECHELEWA: Kodi ya Ksh :amount ya :reference ilistahili :due_date. Tafadhali lipa haraka kuepuka adhabu.',

    'receipt_sent' => 'Risiti :receipt_no ya Ksh :amount imetumwa kwa barua pepe yako. Kumb: :reference. - Chabrin',

    /*
    |--------------------------------------------------------------------------
    | General Notifications (Arifa za Jumla)
    |--------------------------------------------------------------------------
    */
    'welcome' => 'Karibu Chabrin Agencies, :tenant_name! Wasifu wako wa mpangaji umeundwa. Kumb: :tenant_id.',

    'profile_updated' => 'Wasifu wako wa Chabrin umesasishwa. Wasiliana na msaada ikiwa haukufanya mabadiliko haya.',

    'support_ticket' => 'Tikiti ya msaada :ticket_id imeundwa. Tutajibu ndani ya saa 24. - Chabrin Agencies',

    'maintenance_scheduled' => 'Matengenezo yamepangwa kwa :property tarehe :date. Timu yetu itawasiliana nawe. Kumb: :ticket_id.',
];
