<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Session Driver
    |--------------------------------------------------------------------------
    | file: good for local dev (fast, no extra infra)
    | database: recommended for production (auditable, revocable)
    */
    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    | Sessions expire after 120 minutes of inactivity.
    | expire_on_close: also destroys session when browser closes.
    */
    'lifetime'          => env('SESSION_LIFETIME', 120),
    'expire_on_close'   => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    | All session data is encrypted at rest using the app key.
    */
    'encrypt' => env('SESSION_ENCRYPT', true),

    'files'      => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table'      => env('SESSION_TABLE', 'sessions'),
    'store'      => env('SESSION_STORE'),
    'lottery'    => [2, 100],
    'cookie'     => env('SESSION_COOKIE', Str::slug(env('APP_NAME', 'laravel'), '_').'_session'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path & Domain
    |--------------------------------------------------------------------------
    */
    'path'   => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only (Secure Cookie)
    |--------------------------------------------------------------------------
    | Set to true in production (HTTPS). Keep false for local dev (HTTP).
    */
    'secure' => env('SESSION_SECURE_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    | HttpOnly = true means JavaScript CANNOT read the session cookie.
    | This is the #1 defence against XSS-based session theft.
    */
    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    | 'lax'    — protects against CSRF for top-level navigation (recommended)
    | 'strict' — most restrictive; may break OAuth redirects
    | 'none'   — cross-site (requires Secure=true)
    */
    'same_site'      => env('SESSION_SAME_SITE', 'lax'),
    'partitioned'    => false,

];
