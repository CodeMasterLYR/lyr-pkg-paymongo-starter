<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force JSON responses across the entire application.
 *
 * Sets `Accept: application/json` on every inbound request so Laravel
 * always serialises errors, exceptions, and route responses as JSON —
 * never HTML (no welcome page, no Whoops stack traces, no redirects).
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
