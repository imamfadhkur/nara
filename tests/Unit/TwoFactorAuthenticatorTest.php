<?php

namespace Tests\Unit;

use App\Domain\Auth\TwoFactorAuthenticator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticatorTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorAuthenticator $twoFactor;
    private Google2FA $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new Google2FA;
        $this->twoFactor = new TwoFactorAuthenticator($this->engine);
    }

    public function test_two_factor_setup_and_verification_flow(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->hasTwoFactorEnabled());

        // 1. Begin setup
        $setup = $this->twoFactor->beginSetup($user);
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasTwoFactorEnabled()); // Belum confirmed

        // 2. Confirm setup with valid TOTP code
        $validCode = $this->engine->getCurrentOtp($user->two_factor_secret);
        $recoveryCodes = $this->twoFactor->confirmSetup($user, $validCode);

        $this->assertNotNull($recoveryCodes);
        $this->assertCount(8, $recoveryCodes);
        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());

        // 3. Verify valid code
        $this->assertTrue($this->twoFactor->verify($user, $validCode));

        // 4. Verify invalid code
        $this->assertFalse($this->twoFactor->verify($user, '000000'));
    }

    public function test_recovery_code_can_be_used_once_and_is_consumed(): void
    {
        $secret = $this->twoFactor->generateSecret();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['TEST1-RECOV', 'TEST2-RECOV'],
        ]);

        $this->assertTrue($user->hasTwoFactorEnabled());

        // Gunakan kode pemulihan pertama
        $this->assertTrue($this->twoFactor->verify($user, 'TEST1-RECOV'));

        // Cek bahwa kode pemulihan pertama sudah hangus
        $user->refresh();
        $this->assertCount(1, $user->two_factor_recovery_codes);
        $this->assertFalse($this->twoFactor->verify($user, 'TEST1-RECOV'));

        // Kode kedua masih bisa dipakai
        $this->assertTrue($this->twoFactor->verify($user, 'TEST2-RECOV'));
        $user->refresh();
        $this->assertEmpty($user->two_factor_recovery_codes);
    }
}

