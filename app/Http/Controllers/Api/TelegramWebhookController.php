<?php

namespace App\Http\Controllers\Api;

use App\Domain\Integrations\IntegrationService;
use App\Http\Controllers\Controller;
use App\Integrations\Telegram\TelegramWebhookHandler;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramWebhookHandler $handler): JsonResponse
    {
        $envSecret = (string) (config('nara.telegram.webhook_secret') ?? '');
        $hasDbConfig = false;
        $dbSecret = '';

        try {
            $integration = Integration::query()
                ->where('provider', IntegrationService::TELEGRAM)
                ->first();
            if ($integration && is_array($integration->meta) && array_key_exists('webhook_secret', $integration->meta)) {
                $hasDbConfig = true;
                $dbSecret = trim((string) ($integration->meta['webhook_secret'] ?? ''));
            }
        } catch (\Throwable) {
        }

        // Jika sudah diatur melalui Panel Settings (database), prioritaskan aturan database.
        // Tetap terima envSecret sebagai cadangan bila keduanya diisi agar tidak gagal karena perbedaan .env.
        if ($hasDbConfig) {
            if ($dbSecret !== '') {
                $incomingSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
                $allowed = array_values(array_filter([$dbSecret, $envSecret]));
                if (! in_array($incomingSecret, $allowed, true)) {
                    return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
                }
            }
        } elseif ($envSecret !== '') {
            $incomingSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if ($incomingSecret !== $envSecret) {
                return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
            }
        }

        $handler->handle($request->all());

        return response()->json(['ok' => true]);
    }
}

