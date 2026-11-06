<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    */

    'server_key' => env('MIDTRANS_SERVER_KEY'),

    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),

    'is_sanitized' => true,

    'is_3ds' => true,

    /*
    |--------------------------------------------------------------------------
    | QRIS Configuration
    |--------------------------------------------------------------------------
    */

    'qris_acquirer' => env('MIDTRANS_QRIS_ACQUIRER', 'gopay'),

];
