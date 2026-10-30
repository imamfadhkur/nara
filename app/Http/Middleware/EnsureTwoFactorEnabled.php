<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pastikan 2FA TOTP sudah dikonfirmasi pada pengguna (Bagian 13.1).
 *
 * Dikecualikan untuk rute penyetelan 2FA di Settings/Keamanan agar pengguna baru
 * dapat memindai QR pertama kali tanpa terkunci.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('nara.require_two_factor', true)) {
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

