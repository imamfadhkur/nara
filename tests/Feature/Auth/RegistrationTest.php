<?php

test('public registration is disabled', function () {
    // D-12 & Bagian 13.1: Registrasi publik dimatikan; NARA adalah sistem pemilik tunggal
    $response = $this->get('/register');
    $response->assertStatus(404);

    $postResponse = $this->post('/register', [
        'name' => 'Stranger',
        'email' => 'stranger@example.com',
        'password' => 'password12345678',
    ]);
    $postResponse->assertStatus(404);
});
