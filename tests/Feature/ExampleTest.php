<?php

use App\Models\User;

it('redirects guest to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

it('redirects authenticated user to panel projects', function () {
    $user = User::factory()->withTwoFactor()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('panel.projects.index'));
});
