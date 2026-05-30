<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Central Super Admin API Secret
    |--------------------------------------------------------------------------
    |
    | The HMAC-SHA256 secret shared with the super admin panel for
    | verifying webhook signatures. Must match the seller's api_secret
    | configured in the super admin panel.
    |
    */
    'api_secret' => env('CENTRAL_API_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Super Admin URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the super admin panel, used for status check API calls.
    |
    */
    'super_admin_url' => env('CENTRAL_SUPER_ADMIN_URL', ''),
];
