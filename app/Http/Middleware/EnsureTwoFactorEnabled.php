<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pastikan 2FA TOTP sudah dikonfirmasi bila NARA_REQUIRE_2FA diaktifkan.
 * Bila false (default), 2FA merupakan fitur tambahan opsional dan pengguna
 * bebas mengakses serta mengedit seluruh fitur panel tanpa terkunci.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('nara.require_two_factor', false)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if ($request->routeIs('panel.settings.*', 'logout')) {
            return $next($request);
        }

        return redirect()
            ->route('panel.settings.index')
            ->with('warning', 'Aktifkan autentikasi dua langkah (2FA) terlebih dahulu untuk mengamankan akun NARA.');
    }
}

