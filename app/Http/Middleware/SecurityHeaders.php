<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar sejak Fase 0 (Bagian 13.1):
 * CSP dasar, X-Frame-Options, X-Content-Type-Options, dan HSTS saat HTTPS/produksi.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);

        if (! $response->headers->has('Content-Security-Policy')) {
            $viteDev = app()->isLocal() ? ' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173' : '';
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'{$viteDev}",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net{$viteDev}",
                "font-src 'self' https://fonts.bunny.net data:",
                "img-src 'self' data:",
                "connect-src 'self'{$viteDev}",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ]);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        return $response;
    }
}

