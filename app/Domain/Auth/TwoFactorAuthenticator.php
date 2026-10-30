<?php

namespace App\Domain\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * 2FA TOTP + kode pemulihan (D-12, Bagian 13.1).
 *
 * `two_factor_secret` dan `two_factor_recovery_codes` disimpan terenkripsi
 * lewat cast Eloquent pada model User.
 */
class TwoFactorAuthenticator
{
    public const RECOVERY_CODE_COUNT = 8;

    private Google2FA $engine;

    public function __construct(?Google2FA $engine = null)
    {
        $this->engine = $engine ?? new Google2FA;
    }

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    /**
     * Mulai penyetelan: buat secret baru (belum dikonfirmasi) dan kembalikan SVG QR.
     *
     * @return array{secret: string, qr_svg: string, otpauth_url: string}
     */
    public function beginSetup(User $user): array
    {
        $secret = $this->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $url = $this->otpauthUrl($user, $secret);

        return [
            'secret' => $secret,
            'otpauth_url' => $url,
            'qr_svg' => $this->renderQrSvg($url),
        ];
    }

    /**
     * Konfirmasi secret dengan satu kode TOTP yang valid, lalu buat kode pemulihan.
     *
     * @return list<string>|null kode pemulihan bila kode TOTP cocok, null bila salah
     */
    public function confirmSetup(User $user, string $code): ?array
    {
        $secret = $user->two_factor_secret;
        if (! $secret || ! $this->verifyTotp($secret, $code)) {
            return null;
        }

        $codes = $this->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $codes,
        ])->save();

        return $codes;
    }

    /** @return list<string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /** Verifikasi kode 6 digit TOTP ATAU kode pemulihan (dipakai sekali lalu hangus). */
    public function verify(User $user, string $input): bool
    {
        if (! $user->hasTwoFactorEnabled()) {
            return false;
        }

        $clean = preg_replace('/\s+/', '', trim($input)) ?? '';

        if (preg_match('/^\d{6}$/', $clean) === 1 && $this->verifyTotp((string) $user->two_factor_secret, $clean)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, strtoupper($clean));
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        $clean = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($clean) !== 6) {
            return false;
        }

        return (bool) $this->engine->verifyKey($secret, $clean, 1);
    }

    public function otpauthUrl(User $user, ?string $secret = null): string
    {
        return $this->engine->getQRCodeUrl(
            config('app.name', 'NARA'),
            $user->email,
            $secret ?? (string) $user->two_factor_secret,
        );
    }

    public function renderQrSvg(string $content): string
    {
        $renderer = new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($content);
    }

    /** @return list<string> */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = strtoupper(Str::random(5).'-'.Str::random(5));
        }

        return $codes;
    }

    private function consumeRecoveryCode(User $user, string $candidate): bool
    {
        /** @var list<string> $codes */
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $idx => $stored) {
            if (hash_equals(strtoupper($stored), $candidate)) {
                unset($codes[$idx]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}

