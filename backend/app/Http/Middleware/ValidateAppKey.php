<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile App Key Validation Middleware
 *
 * Mobile clients (Android KMP/Ktor, iOS) must send the header:
 *   X-App-Key: <MOBILE_APP_KEY from .env>
 *
 * This identifies the request as originating from an authorized mobile
 * application — similar to how API gateways use client credentials.
 * It does NOT authenticate the user; it identifies the client app.
 *
 * Usage in routes:
 *   Route::middleware(['validate.app.key', 'auth:sanctum'])
 */
class ValidateAppKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $appKey     = config('services.mobile.app_key');
        $clientKey  = $request->header('X-App-Key');

        if (empty($appKey)) {
            // Mobile app key not configured — block all mobile requests
            return response()->json([
                'message' => 'Mobile API is not configured on this server.',
                'error'   => 'mobile_api_disabled',
            ], 503);
        }

        if (! $clientKey || ! hash_equals($appKey, $clientKey)) {
            return response()->json([
                'message' => 'Invalid or missing X-App-Key header.',
                'error'   => 'invalid_app_key',
            ], 401);
        }

        return $next($request);
    }
}
