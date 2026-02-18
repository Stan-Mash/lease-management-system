<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Staff Import Default Password
    |--------------------------------------------------------------------------
    |
    | When importing staff via Excel, new users are created with this password
    | (hashed). If not set, a random 32-character string is used and users
    | must use "Forgot password" to set a password. Prefer leaving unset
    | in production and using password reset flow for imported staff.
    |
    */

    'staff_default_password' => env('IMPORT_STAFF_DEFAULT_PASSWORD'),

];
