<?php

use App\Http\Controllers\Api\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Webhook integrasi eksternal (Telegram Bot, dll).
|
*/

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->name('api.telegram.webhook');
