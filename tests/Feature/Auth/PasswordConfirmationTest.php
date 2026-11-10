<?php

use App\Models\User;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response->assertStatus(200);
});

test('password can be confirmed', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password-panjang-14'),
    ]);

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password-panjang-14',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'wrong-password-14',
    ]);

    $response->assertSessionHasErrors();
});
