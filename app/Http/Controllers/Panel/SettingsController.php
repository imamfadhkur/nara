<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Auth\TwoFactorAuthenticator;
use App\Domain\Notifications\NotificationService;
use App\Domain\Settings\SettingService;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pengaturan NARA: kapasitas, WIP, zona waktu, allowlist Telegram, dan 2FA TOTP.
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly TwoFactorAuthenticator $twoFactor,
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

        return view('panel.settings.index', [
            'user' => $user,
            'settings' => $values,
            'twoFactorSetup' => $setup,
            'recoveryCodes' => session('recovery_codes'),
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
            'telegram_chat_id' => $data['telegram_chat_id'] ?: null,
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

        return redirect()
            ->route('panel.settings.index')
            ->with('toast', 'Pengaturan disimpan.');
    }

    public function testNotification(Request $request, NotificationService $notificationService): RedirectResponse
    {
        $user = $request->user();
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

