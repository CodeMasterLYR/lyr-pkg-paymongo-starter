<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PayMongo
    |--------------------------------------------------------------------------
    | Keys from: https://dashboard.paymongo.com > Developers
    */
    'paymongo' => [
        'public_key'     => env('PAYMONGO_PUBLIC_KEY'),
        'secret_key'     => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'success_url'    => env('PAYMONGO_SUCCESS_URL', 'http://localhost:3000/success'),
        'cancel_url'     => env('PAYMONGO_CANCEL_URL',  'http://localhost:3000/cancel'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Mobile App Identification
    |--------------------------------------------------------------------------
    | Mobile clients (Android KMP, iOS) send X-App-Key: <value> on every
    | request to identify themselves as an authorized app client.
    */
    'mobile' => [
        'app_key' => env('MOBILE_APP_KEY'),
    ],

];
