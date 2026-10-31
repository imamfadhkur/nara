<?php

namespace App\Domain\Notifications;

use App\Domain\Scheduling\CapacityService;
use App\Domain\Settings\SettingService;
use App\Integrations\Telegram\TelegramClient;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Carbon;

/**
 * Layanan Notifikasi dan Reminder NARA (Bagian 7.8 & 8.4).
 *
 * Mengelola pembuatan, deduplikasi, pembatasan jam tenang/kuota harian,
 * pengiriman via Telegram API, dan pencatatan log.
 */
class NotificationService
{
    public function __construct(
        private readonly TelegramClient $telegram,
        private readonly CapacityService $capacity,
        private readonly SettingService $settings,
    ) {}

    /**
     * Kirim briefing pagi ke user via Telegram.
     *
     * @return array{success: bool, message: string, notification?: Notification}
     */
    public function sendDailyBriefing(User $user, bool $force = false): array
    {
        if (empty($user->telegram_chat_id)) {
            return [
                'success' => false,
                'message' => 'User tidak memiliki telegram_chat_id yang terdaftar.',
            ];
        }

        $tz = $user->displayTimezone();
        $today = Carbon::now($tz);
        $dateStr = $today->toDateString();
        $dedupeKey = "daily_briefing:{$user->id}:{$dateStr}";

        // Cek deduplikasi bila tidak dipaksa
        if (! $force && Notification::where('dedupe_key', $dedupeKey)->exists()) {
            return [
                'success' => false,
                'message' => "Briefing pagi sudah pernah dikirim hari ini ({$dateStr}). Gunakan --force untuk kirim ulang.",
            ];
        }

        // Cek pembatas harian (maks 8 notifikasi) bila tidak dipaksa
        if (! $force && $this->isDailyLimitReached($user, $today)) {
            return [
                'success' => false,
                'message' => 'Batas maksimal 8 notifikasi per hari telah tercapai. Gunakan --force untuk melewati.',
            ];
        }

        // Cek jam tenang 22:00–06:30 bila tidak dipaksa
        if (! $force && $this->isQuietHours($today)) {
            return [
                'success' => false,
                'message' => 'Saat ini berada di jam tenang (22:00–06:30). Gunakan --force untuk tetap mengirim.',
            ];
        }

        // Ambil data hari ini
        $todayStartUtc = $today->copy()->startOfDay()->utc();
        $todayEndUtc = $today->copy()->endOfDay()->utc();

        $schedules = Schedule::query()
            ->whereHas('task.project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereBetween('start_at', [$todayStartUtc, $todayEndUtc])
            ->with(['task.project'])
            ->orderBy('start_at')
            ->get();

        $totalMinutes = $schedules->sum(fn (Schedule $s) => $s->durationMinutes());
        $totalDurasiFormatted = $this->formatDuration($totalMinutes);

        // Task terlambat
        $overdueCount = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Waiting])
            ->where('due_date', '<', $dateStr)
            ->count();

        // Tenggat dekat (hari ini atau dalam batas urgent_within_days)
        $urgentDays = $this->settings->int($user, 'urgent_within_days') ?: 3;
        $maxDueDate = $today->copy()->addDays($urgentDays)->toDateString();
        $nearestTask = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$dateStr, $maxDueDate])
            ->orderBy('due_date')
            ->first();

        $tenggatDekatStr = 'Tidak ada';
        if ($nearestTask && $nearestTask->due_date) {
            $diffDays = $today->diffInDays($nearestTask->due_date->copy()->startOfDay(), false);
            $when = match (true) {
                $diffDays <= 0 => 'hari ini',
                $diffDays === 1 => 'besok',
                default => $nearestTask->due_date->locale('id')->translatedFormat('d M'),
            };
            $tenggatDekatStr = htmlspecialchars($nearestTask->title) . " ({$when})";
        }

        // Kapasitas minggu ini
        $cap = $this->capacity->calculateWeekly($user, $today);

        // Susun pesan Telegram sesuai format Bagian 8.4
        $dayFormatted = $today->locale('id')->translatedFormat('l, d M Y');
        $text = "☀️ <b>Selamat pagi — {$dayFormatted}</b>\n\n";
        $text .= "• <b>Hari ini:</b> {$schedules->count()} jadwal ({$totalDurasiFormatted})\n";
        $text .= "• <b>Terlambat:</b> {$overdueCount} task\n";
        $text .= "• <b>Tenggat dekat:</b> {$tenggatDekatStr}\n";
        $text .= "• <b>Kapasitas minggu ini:</b> {$cap['percent']}% ({$cap['status']})\n";

        if ($schedules->isNotEmpty()) {
            $text .= "\n<b>Jadwal Hari Ini:</b>\n";
            foreach ($schedules as $s) {
                $time = $s->start_at->setTimezone($tz)->format('H:i') . '–' . $s->end_at->setTimezone($tz)->format('H:i');
                $taskTitle = htmlspecialchars($s->task->title);
                $projectName = htmlspecialchars($s->task->project->name ?? '-');
                $text .= "• <code>{$time}</code> <b>{$taskTitle}</b> (#{$s->task->id})\n";
                $text .= "  <i>{$projectName}</i> — /done_{$s->task->id}\n";
            }
        }

        // Kirim Telegram
        $sent = $this->telegram->sendMessage($user->telegram_chat_id, $text);

        // Simpan Notification
        $notification = Notification::updateOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
                'user_id' => $user->id,
                'type' => 'daily_briefing',
                'payload' => [
                    'schedules_count' => $schedules->count(),
                    'total_minutes' => $totalMinutes,
                    'overdue_count' => $overdueCount,
                    'capacity_percent' => $cap['percent'],
                    'capacity_status' => $cap['status'],
                    'text' => $text,
                ],
                'scheduled_at' => Carbon::now('UTC'),
            ]
        );

        // Simpan NotificationLog
        NotificationLog::create([
            'notification_id' => $notification->id,
            'channel' => 'telegram',
            'status' => $sent ? 'sent' : 'failed',
            'error' => $sent ? null : 'Gagal mengirim melalui Telegram API.',
            'sent_at' => Carbon::now('UTC'),
        ]);

        return [
            'success' => $sent,
            'message' => $sent
                ? 'Briefing pagi berhasil dikirim ke Telegram.'
                : 'Gagal mengirim pesan briefing via Telegram Bot API.',
            'notification' => $notification,
        ];
    }

    /**
     * Kirim pengingat jadwal sesi task ke Telegram.
     *
     * @return array{success: bool, message: string, notification?: Notification}
     */
    public function sendScheduleReminder(Schedule $schedule, bool $force = false): array
    {
        $schedule->loadMissing(['task.project.user']);
        $task = $schedule->task;
        $project = $task?->project;
        $user = $project?->user;

        if (! $user || empty($user->telegram_chat_id)) {
            return [
                'success' => false,
                'message' => 'User atau telegram_chat_id tidak ditemukan untuk jadwal ini.',
            ];
        }

        $dedupeKey = "reminder:schedule:{$schedule->id}";

        if (! $force && Notification::where('dedupe_key', $dedupeKey)->exists()) {
            return [
                'success' => false,
                'message' => "Pengingat untuk jadwal #{$schedule->id} sudah pernah dikirim. Gunakan --force untuk kirim ulang.",
            ];
        }

        // Jika task sudah selesai atau dibatalkan, jangan ingatkan kecuali dipaksa
        if (! $force && in_array($task->status, [TaskStatus::Done, TaskStatus::Cancelled], true)) {
            return [
                'success' => false,
                'message' => "Task #{$task->id} sudah dalam status {$task->status->value}.",
            ];
        }

        $tz = $user->displayTimezone();
        $startTime = $schedule->start_at->setTimezone($tz)->format('H:i');
        $duration = $schedule->durationMinutes();
        $taskTitle = htmlspecialchars($task->title);
        $projectName = htmlspecialchars($project->name);

        // Format pesan per Bagian 8.4
        $text = "⏰ <b>Pengingat Jadwal ({$startTime}):</b>\n";
        $text .= "<b>{$taskTitle}</b>\n";
        $text .= "Project: <i>{$projectName}</i>\n";
        $text .= "Durasi: {$duration} menit\n\n";
        $text .= "↳ Selesai: /done_{$task->id}";

        $sent = $this->telegram->sendMessage($user->telegram_chat_id, $text);

        $notification = Notification::updateOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
                'user_id' => $user->id,
                'type' => 'reminder',
                'payload' => [
                    'schedule_id' => $schedule->id,
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'project_name' => $project->name,
                    'start_at' => $schedule->start_at->toIso8601String(),
                    'duration_minutes' => $duration,
                    'text' => $text,
                ],
                'scheduled_at' => Carbon::now('UTC'),
            ]
        );

        NotificationLog::create([
            'notification_id' => $notification->id,
            'channel' => 'telegram',
            'status' => $sent ? 'sent' : 'failed',
            'error' => $sent ? null : 'Gagal mengirim pengingat via Telegram API.',
            'sent_at' => Carbon::now('UTC'),
        ]);

        return [
            'success' => $sent,
            'message' => $sent
                ? "Pengingat jadwal #{$schedule->id} ({$task->title}) berhasil dikirim ke Telegram."
                : 'Gagal mengirim pengingat via Telegram Bot API.',
            'notification' => $notification,
        ];
    }

    /**
     * Periksa dan kirim reminder jadwal yang mendekati waktu mulai.
     * (Mis. 30 menit sebelum start_at).
     */
    public function dispatchDueReminders(?Carbon $now = null): int
    {
        $nowUtc = ($now ?? Carbon::now('UTC'))->copy()->utc();
        $windowEndUtc = $nowUtc->copy()->addMinutes(35);

        $schedules = Schedule::query()
            ->whereBetween('start_at', [$nowUtc, $windowEndUtc])
            ->whereHas('task', fn ($q) => $q->whereNotIn('status', [TaskStatus::Done, TaskStatus::Cancelled]))
            ->whereHas('task.project.user', fn ($q) => $q->whereNotNull('telegram_chat_id'))
            ->with(['task.project.user'])
            ->get();

        $sentCount = 0;
        foreach ($schedules as $schedule) {
            $dedupeKey = "reminder:schedule:{$schedule->id}";
            if (! Notification::where('dedupe_key', $dedupeKey)->exists()) {
                $res = $this->sendScheduleReminder($schedule);
                if ($res['success']) {
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    /**
     * Periksa dan kirim briefing pagi untuk user yang waktunya tiba.
     */
    public function dispatchDueBriefings(?Carbon $now = null): int
    {
        $users = User::query()
            ->whereNotNull('telegram_chat_id')
            ->get();

        $sentCount = 0;
        foreach ($users as $user) {
            $tz = $user->displayTimezone();
            $userNow = ($now ?? Carbon::now($tz))->copy()->setTimezone($tz);
            $briefingTime = $this->settings->get($user, 'briefing_time') ?: '07:00';

            [$bHour, $bMin] = explode(':', $briefingTime);
            $briefingToday = $userNow->copy()->setTime((int) $bHour, (int) $bMin, 0);

            if ($userNow->greaterThanOrEqualTo($briefingToday)) {
                $dateStr = $userNow->toDateString();
                $dedupeKey = "daily_briefing:{$user->id}:{$dateStr}";

                if (! Notification::where('dedupe_key', $dedupeKey)->exists()) {
                    $res = $this->sendDailyBriefing($user);
                    if ($res['success']) {
                        $sentCount++;
                    }
                }
            }
        }

        return $sentCount;
    }

    private function isQuietHours(Carbon $time): bool
    {
        $hour = (int) $time->format('H');
        $min = (int) $time->format('i');
        $timeDecimal = $hour + ($min / 60);

        // Jam tenang: 22:00 s.d. 06:30
        return ($timeDecimal >= 22.0 || $timeDecimal < 6.5);
    }

    private function isDailyLimitReached(User $user, Carbon $today): bool
    {
        $startUtc = $today->copy()->startOfDay()->utc();
        $endUtc = $today->copy()->endOfDay()->utc();

        $count = Notification::where('user_id', $user->id)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->count();

        return $count >= 8;
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 menit';
        }

        $hours = intdiv($minutes, 60);
        $remMinutes = $minutes % 60;

        if ($hours === 0) {
            return "{$remMinutes} menit";
        }

        if ($remMinutes === 0) {
            return "{$hours} jam";
        }

        return "{$hours} jam {$remMinutes} menit";
    }
}
