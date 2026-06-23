<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "connect-src 'self' http://localhost:* http://127.0.0.1:* http://0.0.0.0:* ws://localhost:* ws://127.0.0.1:* ws://0.0.0.0:*",
            "font-src 'self' data:",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "img-src 'self' data: blob: https:",
            "object-src 'none'",
            "script-src 'self' 'unsafe-inline' http://localhost:* http://127.0.0.1:* http://0.0.0.0:*",
            "style-src 'self' 'unsafe-inline' http://localhost:* http://127.0.0.1:* http://0.0.0.0:*",
        ]));

        return $response;
    }
}
