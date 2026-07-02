<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $directives = [
            'default-src' => "default-src 'self'",
            'base-uri' => "base-uri 'self'",
            'connect-src' => "connect-src 'self'",
            'font-src' => "font-src 'self' data:",
            'form-action' => "form-action 'self'",
            'frame-ancestors' => "frame-ancestors 'none'",
            'img-src' => "img-src 'self' data: blob: https:",
            'object-src' => "object-src 'none'",
            'script-src' => "script-src 'self' 'nonce-".Vite::cspNonce()."'",
            'style-src' => "style-src 'self'",
        ];

        if (in_array(config('app.env'), ['local', 'testing'], true)) {
            $directives['connect-src'] = "connect-src 'self' http://localhost:* http://127.0.0.1:* http://0.0.0.0:* ws://localhost:* ws://127.0.0.1:* ws://0.0.0.0:*";
            $directives['script-src'] = "script-src 'self' 'unsafe-inline' http://localhost:* http://127.0.0.1:* http://0.0.0.0:*";
            $directives['style-src'] = "style-src 'self' 'unsafe-inline' http://localhost:* http://127.0.0.1:* http://0.0.0.0:*";
        }

        return implode('; ', $directives);
    }
}
