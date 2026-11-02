<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Auth\TwoFactorAuthenticator;
use App\Domain\Integrations\IntegrationService;
use App\Domain\Notifications\NotificationService;
use App\Domain\Scheduling\GoogleCalendarSyncService;
use App\Domain\Settings\SettingService;
use App\Http\Controllers\Controller;
use App\Integrations\Google\GoogleCalendarClient;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pengaturan NARA: kapasitas, WIP, zona waktu, integrasi eksternal (Fase 2), dan 2FA TOTP.
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly TwoFactorAuthenticator $twoFactor,
        private readonly IntegrationService $integrations,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $values = $this->settings->all($user);

        $setup = null;
        if (! $user->hasTwoFactorEnabled() && $user->two_factor_secret) {
            $url = $this->twoFactor->otpauthUrl($user);
            $setup = [
                'secret' => $user->two_factor_secret,
                'qr_svg' => $this->twoFactor->renderQrSvg($url),
            ];
        }

        $goals = $user->goals()->with('lifeDomain')->withCount([
            'projects as active_projects_count' => fn ($q) => $q->where('status', 'active'),
            'projects as total_projects_count',
        ])->orderBy('title')->get();

        return view('panel.settings.index', [
            'user' => $user,
            'settings' => $values,
            'goals' => $goals,
            'twoFactorSetup' => $setup,
            'recoveryCodes' => session('recovery_codes'),
            'telegramConfig' => $this->integrations->getTelegramConfig($user),
            'googleConfig' => $this->integrations->getGoogleCalendarConfig($user),
            'isTelegramEnabled' => $this->integrations->isTelegramEnabled($user),
            'isGoogleEnabled' => $this->integrations->isGoogleCalendarEnabled($user),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'telegram_chat_id' => ['nullable', 'string', 'max:64'],
            'weekly_capacity_minutes' => ['required', 'integer', 'min:60', 'max:6000'],
            'capacity_warn_percent' => ['required', 'integer', 'min:50', 'max:100'],
            'capacity_over_percent' => ['required', 'integer', 'min:80', 'max:200'],
            'wip_limit_per_slot' => ['required', 'integer', 'min:1', 'max:10'],
            'stagnation_days' => ['required', 'integer', 'min:1', 'max:60'],
            'urgent_within_days' => ['required', 'integer', 'min:0', 'max:30'],
            'briefing_time' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'default_reminder_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
            'nara_budget_minutes_per_week' => ['required', 'integer', 'min:60', 'max:2400'],
            'admin_budget_minutes_per_day' => ['required', 'integer', 'min:1', 'max:60'],
        ], [
            'timezone.in' => 'Zona waktu tidak dikenali.',
            'weekly_capacity_minutes.min' => 'Kapasitas mingguan minimal 60 menit (1 jam).',
            'wip_limit_per_slot.min' => 'Batas WIP minimal 1 project per slot.',
        ]);

        $user->update([
            'timezone' => $data['timezone'],
            'telegram_chat_id' => ($data['telegram_chat_id'] ?? null) ?: null,
        ]);

        foreach ([
            'weekly_capacity_minutes', 'capacity_warn_percent', 'capacity_over_percent',
            'wip_limit_per_slot', 'stagnation_days', 'urgent_within_days',
            'nara_budget_minutes_per_week', 'admin_budget_minutes_per_day',
        ] as $key) {
            $this->settings->set($user, $key, (int) $data[$key]);
        }

        if (! empty($data['briefing_time'])) {
            $this->settings->set($user, 'briefing_time', $data['briefing_time']);
        }
        if (! empty($data['default_reminder_minutes'])) {
            $this->settings->set($user, 'default_reminder_minutes', (int) $data['default_reminder_minutes']);
        }

        if ($request->has('goal_wip_limits') && is_array($request->input('goal_wip_limits'))) {
            foreach ($request->input('goal_wip_limits') as $goalId => $limitVal) {
                $parsedLimit = ($limitVal !== '' && $limitVal !== null && (int) $limitVal > 0) ? (int) $limitVal : null;
                $user->goals()->where('goals.id', $goalId)->update(['wip_limit' => $parsedLimit]);
            }
        }

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', 'Pengaturan disimpan.');
    }

    /**
     * Simpan pengaturan integrasi Telegram dinamis ke database (Fase 2).
     */
    public function updateTelegramIntegration(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'is_active' => 'nullable|boolean',
            'bot_token' => 'nullable|string|max:255',
            'chat_id' => 'nullable|string|max:64',
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        $this->integrations->updateTelegram($user, [
            'is_active' => $request->boolean('is_active'),
            'bot_token' => $data['bot_token'] ?? '',
            'chat_id' => $data['chat_id'] ?? '',
            'webhook_secret' => $data['webhook_secret'] ?? '',
        ]);

        $statusStr = $request->boolean('is_active') ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', "Pengaturan integrasi Telegram berhasil diperbarui ({$statusStr}).");
    }

    /**
     * Simpan pengaturan integrasi Google Calendar dinamis ke database (Fase 2).
     */
    public function updateGoogleIntegration(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'is_active' => 'nullable|boolean',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'calendar_id' => 'nullable|string|max:255',
            'sync_mode' => 'nullable|string|in:two_way,one_way,read_only',
        ]);

        $this->integrations->updateGoogleCalendar($user, [
            'is_active' => $request->boolean('is_active'),
            'client_id' => $data['client_id'] ?? '',
            'client_secret' => $data['client_secret'] ?? '',
            'calendar_id' => $data['calendar_id'] ?? 'NARA',
            'sync_mode' => $data['sync_mode'] ?? 'two_way',
        ]);

        $statusStr = $request->boolean('is_active') ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', "Pengaturan integrasi Google Calendar berhasil diperbarui ({$statusStr}).");
    }

    /**
     * Alihkan pengguna ke Google OAuth Consent Screen.
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $user = $request->user();
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);
        $meta = $integration->meta ?? [];

        $clientId = $meta['client_id'] ?? env('GOOGLE_CLIENT_ID');
        $clientSecret = $meta['client_secret'] ?? env('GOOGLE_CLIENT_SECRET');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->to(route('panel.settings.index') . '#integrations')
                ->withErrors(['general' => 'Google Client ID dan Client Secret belum lengkap. Silakan isi dan simpan terlebih dahulu.']);
        }

        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        $redirectUri = route('panel.settings.integrations.google.callback');
        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
            'state' => $state,
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    /**
     * Tangani pengalihan balik (callback) dari Google OAuth 2.0.
     */
    public function handleGoogleCallback(
        Request $request,
        GoogleCalendarClient $googleClient,
        GoogleCalendarSyncService $calendarSync,
    ): RedirectResponse {
        $user = $request->user();

        if ($request->has('error')) {
            return redirect()->to(route('panel.settings.index') . '#integrations')
                ->withErrors(['general' => 'Otorisasi Google Calendar dibatalkan atau ditolak: ' . $request->query('error')]);
        }

        $savedState = session('google_oauth_state');
        session()->forget('google_oauth_state');

        if (empty($savedState) || $request->query('state') !== $savedState) {
            return redirect()->to(route('panel.settings.index') . '#integrations')
                ->withErrors(['general' => 'Validasi state keamanan OAuth gagal. Silakan ulangi otorisasi.']);
        }

        $code = $request->query('code');
        if (empty($code)) {
            return redirect()->to(route('panel.settings.index') . '#integrations')
                ->withErrors(['general' => 'Kode otorisasi dari Google tidak ditemukan.']);
        }

        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);
        $meta = $integration->meta ?? [];
        $clientId = $meta['client_id'] ?? env('GOOGLE_CLIENT_ID');
        $clientSecret = $meta['client_secret'] ?? env('GOOGLE_CLIENT_SECRET');
        $redirectUri = route('panel.settings.integrations.google.callback');

        try {
            $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (! $response->successful()) {
                $errorDesc = $response->json('error_description') ?? $response->body();

                return redirect()->to(route('panel.settings.index') . '#integrations')
                    ->withErrors(['general' => "Gagal otorisasi dengan Google ({$response->status()}): {$errorDesc}"]);
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? $integration->refresh_token;
            $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);
            $expiresAt = Carbon::now('UTC')->addSeconds($expiresIn - 60);

            $integration->update([
                'is_active' => true,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt,
            ]);

            // Buat atau pastikan kalender sekunder khusus 'NARA' di Google Calendar pengguna
            $calendarName = $meta['calendar_id'] ?: 'NARA';
            $googleClient->ensureDedicatedCalendar($user, $calendarName);

            // Sinkronkan seluruh jadwal yang ada di NARA ke Google Calendar
            $synced = $calendarSync->syncAll($user);
            $calendarSync->pullExternalBusyBlocks($user, Carbon::now('UTC')->subDays(7), Carbon::now('UTC')->addDays(30));

            return redirect()->to(route('panel.settings.index') . '#integrations')
                ->with('status', "Berhasil menghubungkan Akun Google! Kalender '{$calendarName}' aktif. {$synced} sesi jadwal telah disinkronkan ke Google Calendar.");
        } catch (\Throwable $e) {
            return redirect()->to(route('panel.settings.index') . '#integrations')
                ->withErrors(['general' => 'Terjadi kesalahan saat menghubungkan ke Google: ' . $e->getMessage()]);
        }
    }

    /**
     * Putuskan akun Google Calendar.
     */
    public function disconnectGoogle(Request $request): RedirectResponse
    {
        $user = $request->user();
        $integration = $this->integrations->getIntegration($user, IntegrationService::GOOGLE_CALENDAR);

        $meta = $integration->meta ?? [];
        unset($meta['google_calendar_id']);

        $integration->update([
            'access_token' => null,
            'refresh_token' => null,
            'expires_at' => null,
            'meta' => $meta,
        ]);

        return redirect()->to(route('panel.settings.index') . '#integrations')
            ->with('status', 'Koneksi akun Google Calendar berhasil diputuskan. Kalender kembali ke Mode Lokal.');
    }

    /**
     * Toggle cepat on/off integrasi pihak ketiga.
     */
    public function toggleIntegration(Request $request, string $provider): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($provider, [IntegrationService::TELEGRAM, IntegrationService::GOOGLE_CALENDAR], true), 404);

        $integration = $this->integrations->toggle($user, $provider);
        $state = $integration->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $name = $provider === IntegrationService::TELEGRAM ? 'Telegram Bot' : 'Google Calendar';

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', "Integrasi {$name} berhasil {$state}.");
    }

    public function testNotification(Request $request, NotificationService $notificationService): RedirectResponse
    {
        $user = $request->user();

        if (! $this->integrations->isTelegramEnabled($user)) {
            return redirect()
                ->route('panel.settings.index')
                ->with('toast', 'Gagal: Integrasi Telegram saat ini sedang dinonaktifkan.');
        }

        if (empty($user->telegram_chat_id)) {
            return redirect()
                ->route('panel.settings.index')
                ->with('toast', 'Gagal: Telegram Chat ID belum diisi.');
        }

        $type = $request->input('type', 'briefing');

        if ($type === 'briefing') {
            $res = $notificationService->sendDailyBriefing($user, force: true);
        } else {
            $schedule = Schedule::query()
                ->whereHas('task.project', fn ($q) => $q->where('user_id', $user->id))
                ->orderBy('start_at')
                ->first();

            if (! $schedule) {
                return redirect()
                    ->route('panel.settings.index')
                    ->with('toast', 'Gagal: Belum ada jadwal (schedule) di sistem untuk diuji coba.');
            }

            $res = $notificationService->sendScheduleReminder($schedule, force: true);
        }

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', ($res['success'] ? '✓ ' : '! ') . $res['message']);
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $this->twoFactor->beginSetup($request->user());

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', 'Pindai kode QR di aplikasi autentikator, lalu masukkan kode 6 digit untuk mengonfirmasi.');
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:16']]);

        $codes = $this->twoFactor->confirmSetup($request->user(), $request->string('code')->toString());
        if ($codes === null) {
            throw ValidationException::withMessages([
                'code' => 'Kode 6 digit tidak cocok. Pastikan jam perangkat Anda sinkron.',
            ]);
        }

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', '2FA aktif. Simpan kode pemulihan di bawah ini di tempat offline yang aman.')
            ->with('recovery_codes', $codes);
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.current_password' => 'Kata sandi saat ini tidak sesuai.',
        ]);

        $codes = $this->twoFactor->regenerateRecoveryCodes($request->user());

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', 'Kode pemulihan baru dibuat. Kode lama tidak berlaku lagi.')
            ->with('recovery_codes', $codes);
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.current_password' => 'Kata sandi saat ini tidak sesuai.',
        ]);

        $this->twoFactor->disable($request->user());

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', '2FA dinonaktifkan.');
    }
}
