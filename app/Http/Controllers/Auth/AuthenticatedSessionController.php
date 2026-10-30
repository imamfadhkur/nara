<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public const PENDING_USER_SESSION_KEY = 'auth.two_factor.pending_user_id';

    public const PENDING_REMEMBER_SESSION_KEY = 'auth.two_factor.remember';

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Bila pengguna sudah mengaktifkan 2FA (D-12), sesi belum di-login penuh:
     * id disimpan sementara di session lalu diarahkan ke halaman tantangan TOTP.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->validateCredentials();

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->regenerate();
            $request->session()->put(self::PENDING_USER_SESSION_KEY, $user->id);
            $request->session()->put(self::PENDING_REMEMBER_SESSION_KEY, $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
