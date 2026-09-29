<?php

return [
    'midtrans_key' => env('MIDTRANS_SERVER_KEY'),
    'midtrans_base' => 'https://app.sandbox.midtrans.com',
    'fonnte_token' => env('FONNTE_TOKEN'),
    'whatsapp_enabled' => (bool) env('WHATSAPP_ENABLED', false),
    'simulated_fee' => env('SANDBOX_FEE_RUPIAH'),
];
