<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\PayMongoController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------------
// Webhook — No auth at all; verified by PayMongo HMAC signature
// ------------------------------------------------------------------
Route::post('/webhooks/paymongo', [PayMongoController::class, 'webhook']);

// ------------------------------------------------------------------
// WEB / SPA Auth Routes (Session-based — Next.js, browser apps)
// Sanctum stateful: uses HttpOnly cookie + CSRF (X-XSRF-TOKEN)
// ------------------------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
});

// ------------------------------------------------------------------
// MOBILE Auth Routes (Bearer Token — Android KMP/Ktor, iOS)
// Requires: X-App-Key: <MOBILE_APP_KEY> header to identify the app
// Login returns a Sanctum Personal Access Token (store securely on device)
// ------------------------------------------------------------------
Route::prefix('mobile')->middleware('validate.app.key')->group(function () {

    // Public mobile auth (no user token required yet, but app key required)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [MobileAuthController::class, 'register']);
        Route::post('/login',    [MobileAuthController::class, 'login']);
    });

    // Protected mobile routes (app key + user Bearer token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout',     [MobileAuthController::class, 'logout']);
            Route::post('/logout-all', [MobileAuthController::class, 'logoutAll']);
            Route::get('/me',          [MobileAuthController::class, 'me']);
        });

        // PayMongo — same controllers, works for both web session & mobile token
        Route::prefix('paymongo')->group(function () {
            Route::post('/checkout',     [PayMongoController::class, 'checkout']);
            Route::get('/checkout/{id}', [PayMongoController::class, 'getSession']);
            Route::get('/payments',      [PayMongoController::class, 'listPayments']);
        });
    });
});

// ------------------------------------------------------------------
// WEB Protected Routes — Session auth (Sanctum stateful SPA)
// ------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('paymongo')->group(function () {
        Route::post('/checkout',     [PayMongoController::class, 'checkout']);
        Route::get('/checkout/{id}', [PayMongoController::class, 'getSession']);
        Route::get('/payments',      [PayMongoController::class, 'listPayments']);
    });
});
