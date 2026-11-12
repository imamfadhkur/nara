<?php

use App\View\Components\Toast;

test('toast component handles string session toast', function () {
    session(['toast' => 'Operasi berhasil']);

    $component = new Toast();

    expect($component->message)->toBe('Operasi berhasil')
        ->and($component->type)->toBe('info');
});

test('toast component handles array session toast with type and message', function () {
    session(['toast' => ['type' => 'success', 'message' => 'Sesi fokus tersimpan']]);

    $component = new Toast();

    expect($component->message)->toBe('Sesi fokus tersimpan')
        ->and($component->type)->toBe('success');
});

test('toast component handles warning session', function () {
    session(['warning' => 'Perhatian']);

    $component = new Toast();

    expect($component->message)->toBe('Perhatian')
        ->and($component->type)->toBe('warning');
});

test('toast component handles direct parameters', function () {
    $component = new Toast(message: 'Halo', type: 'success');

    expect($component->message)->toBe('Halo')
        ->and($component->type)->toBe('success');
});

test('toast component handles null session gracefully', function () {
    session()->forget(['toast', 'warning']);

    $component = new Toast();

    expect($component->message)->toBeNull()
        ->and($component->type)->toBe('info');
});
