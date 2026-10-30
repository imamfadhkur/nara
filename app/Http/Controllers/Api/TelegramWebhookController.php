<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Integrations\Telegram\TelegramWebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramWebhookHandler $handler): JsonResponse
    {
        $secret = config('nara.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        $handler->handle($request->all());

        return response()->json(['ok' => true]);
    }
}
