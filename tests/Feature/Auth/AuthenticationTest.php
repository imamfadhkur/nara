<?php

use App\Domain\Auth\TwoFactorAuthenticator;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users without 2fa can authenticate directly', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password-panjang-14'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password-panjang-14',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with 2fa enabled are redirected to 2fa challenge', function () {
    $user = User::factory()->withTwoFactor('JBSWY3DPEHPK3PXP')->create([
        'password' => bcrypt('password-panjang-14'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password-panjang-14',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('two-factor.challenge'));

    // Buka layar challenge
    $this->get(route('two-factor.challenge'))->assertStatus(200);

    // Kirim kode yang salah
    $failResponse = $this->post(route('two-factor.verify'), ['code' => '999999']);
    $failResponse->assertSessionHasErrors('code');
    $this->assertGuest();

    // Kirim kode pemulihan yang sah
    $successResponse = $this->post(route('two-factor.verify'), ['code' => 'AAAAA-BBBBB']);
    $this->assertAuthenticatedAs($user);
    $successResponse->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password-14',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
