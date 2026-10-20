<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session Hijacking Prevention Middleware
 *
 * Binds the authenticated session to the original User-Agent and IP address.
 * If either changes mid-session, the session is immediately invalidated and
 * the user is logged out — a classic defence against session fixation and
 * cookie-theft attacks.
 */
class PreventSessionHijacking
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to requests that have an active authenticated session
        if (! Auth::check()) {
            return $next($request);
        }

        // Skip for token-authenticated requests (mobile Bearer token clients)
        // Token auth has no session — hijack checks are handled by token revocation
        if ($request->user()?->currentAccessToken() instanceof \Laravel\Sanctum\PersonalAccessToken) {
            return $next($request);
        }

        $sessionUa  = $request->session()->get('_auth_ua');
        $sessionIp  = $request->session()->get('_auth_ip');
        $currentUa  = $request->userAgent() ?? '';
        $currentIp  = $request->ip();

        // First authenticated request — store fingerprint
        if (! $sessionUa && ! $sessionIp) {
            $request->session()->put('_auth_ua', hash('sha256', $currentUa));
            $request->session()->put('_auth_ip', $currentIp);
            return $next($request);
        }

        // Validate User-Agent fingerprint
        if ($sessionUa && ! hash_equals($sessionUa, hash('sha256', $currentUa))) {
            Log::warning('Session hijack attempt (UA mismatch)', [
                'user_id'    => Auth::id(),
                'ip'         => $currentIp,
                'stored_ua'  => $sessionUa,
            ]);
            return $this->invalidateAndRespond($request);
        }

        // Validate IP address
        if ($sessionIp && $sessionIp !== $currentIp) {
            Log::warning('Session hijack attempt (IP mismatch)', [
                'user_id'   => Auth::id(),
                'stored_ip' => $sessionIp,
                'current_ip'=> $currentIp,
            ]);
            return $this->invalidateAndRespond($request);
        }

        return $next($request);
    }

    private function invalidateAndRespond(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Session terminated. Please log in again.',
            'reason'  => 'security_violation',
        ], 401);
    }
}
