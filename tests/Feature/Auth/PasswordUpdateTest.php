<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password-panjang-14'),
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password-panjang-14',
            'password' => 'new-password-panjang-14',
            'password_confirmation' => 'new-password-panjang-14',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('new-password-panjang-14', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password-panjang-14'),
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password-14',
            'password' => 'new-password-panjang-14',
            'password_confirmation' => 'new-password-panjang-14',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});

test('password with at least 6 characters can be updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password-lama-12'),
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password-lama-12',
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('123456', $user->refresh()->password));
});

test('password with less than 6 characters fails validation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password-lama-12'),
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password-lama-12',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'password')
        ->assertRedirect('/profile');
});
