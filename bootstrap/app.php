<?php

use App\Domain\DomainRuleException;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            '/',
            'api/*',
            'telegram/*',
            'panel/telegram/*',
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'two-factor' => EnsureTwoFactorEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Konversi DomainRuleException menjadi pesan validasi yang ramah di form/JSON.
        $exceptions->render(function (DomainRuleException $e, Request $request) {
            throw ValidationException::withMessages([
                $e->field => $e->getMessage(),
            ]);
        });
    })->create();
