<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Tantangan langkah kedua saat login (TOTP 6 digit atau kode pemulihan).
 */
class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ], [
            'code.required' => 'Masukkan kode 6 digit atau kode pemulihan.',
        ]);

        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $throttleKey = 'two-factor:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'code' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! $user->verifyTwoFactorCode($request->string('code')->toString())) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'code' => 'Kode autentikasi dua langkah tidak valid.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $remember = (bool) $request->session()->pull(AuthenticatedSessionController::PENDING_REMEMBER_SESSION_KEY, false);
        $request->session()->forget(AuthenticatedSessionController::PENDING_USER_SESSION_KEY);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get(AuthenticatedSessionController::PENDING_USER_SESSION_KEY);

        return $id ? User::find($id) : null;
    }
}

