<?php

namespace App\Integrations\Telegram;

use App\Domain\Capture\InboxService;
use App\Domain\Scheduling\CapacityService;
use App\Domain\Tasks\TaskService;
use App\Models\InboxItem;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\ActivitySource;
use App\Support\Enums\InboxStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TelegramWebhookHandler
{
    public function __construct(
        private readonly TelegramClient $client,
        private readonly InboxService $inbox,
        private readonly CapacityService $capacity,
        private readonly TaskService $tasks,
    ) {}

    /**
     * Proses update webhook dari Telegram API.
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): void
    {
        $message = $payload['message'] ?? null;
        if (! $message) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $messageId = (string) ($message['message_id'] ?? '');
        $text = trim((string) ($message['text'] ?? ''));

        if ($chatId === '' || $text === '') {
            return;
        }

        // 1. Allowlist Keamanan (D-12 & Bagian 8.6): Hanya chat_id terdaftar yang dilayani
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (! $user) {
            Log::info("Telegram webhook diabaikan (chat_id {$chatId} tidak terdaftar di allowlist).");
            return;
        }

        // 2. Cegah duplikasi webhook (Bagian 8.6)
        if ($messageId !== '' && InboxItem::where('user_id', $user->id)->where('telegram_message_id', $messageId)->exists()) {
            return;
        }

        // 3. Routing Perintah
        if (str_starts_with($text, '/')) {
            $parts = explode(' ', $text, 2);
            $command = strtolower($parts[0]);
            $argument = trim($parts[1] ?? '');

            match ($command) {
                '/add' => $this->handleAdd($user, $chatId, $argument, $messageId),
                '/idea' => $this->handleIdea($user, $chatId, $argument, $messageId),
                '/inbox' => $this->handleInbox($user, $chatId),
                '/cap' => $this->handleCapacity($user, $chatId),
                '/today' => $this->handleToday($user, $chatId),
                '/done' => $this->handleDone($user, $chatId, $argument),
                default => $this->handleHelp($chatId),
            };
        } else {
            // Teks biasa diperlakukan sebagai /add (Bagian 8.2)
            $this->handleAdd($user, $chatId, $text, $messageId);
        }
    }

    private function handleAdd(User $user, string $chatId, string $text, string $messageId): void
    {
        if ($text === '') {
            $this->client->sendMessage($chatId, "⚠️ Masukkan teks tugas. Contoh: <code>/add Kirim invoice ke klien</code>");
            return;
        }

        $item = $this->inbox->capture($user, $text, ActivitySource::Telegram, hint: 'task', telegramMsgId: $messageId);
        $pendingCount = InboxItem::where('user_id', $user->id)->where('status', InboxStatus::Pending)->count();

        $this->client->sendMessage($chatId, "✓ Masuk Inbox (<b>#{$item->id}</b>). {$pendingCount} item menunggu.");
    }

    private function handleIdea(User $user, string $chatId, string $text, string $messageId): void
    {
        if ($text === '') {
            $this->client->sendMessage($chatId, "⚠️ Masukkan gagasan. Contoh: <code>/idea Fitur voice note di NARA</code>");
            return;
        }

        $item = $this->inbox->capture($user, $text, ActivitySource::Telegram, hint: 'idea', telegramMsgId: $messageId);

        $this->client->sendMessage($chatId, "✓ Ide disimpan di Inbox (<b>#{$item->id}</b>).");
    }

    private function handleInbox(User $user, string $chatId): void
    {
        $items = InboxItem::where('user_id', $user->id)
            ->where('status', InboxStatus::Pending)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $total = InboxItem::where('user_id', $user->id)
            ->where('status', InboxStatus::Pending)
            ->count();

        if ($items->isEmpty()) {
            $this->client->sendMessage($chatId, "Inbox bersih! Tidak ada item yang menunggu triase.");
            return;
        }

        $msg = "📥 <b>Inbox Menunggu ({$total} item)</b>\n\n";
        foreach ($items as $idx => $item) {
            $hintBadge = $item->hint ? " [{$item->hint}]" : "";
            $msg .= ($idx + 1) . ". #{$item->id} " . htmlspecialchars($item->content) . "{$hintBadge}\n";
        }
        if ($total > 5) {
            $msg .= "\n<i>...dan " . ($total - 5) . " item lainnya di web.</i>";
        }

        $this->client->sendMessage($chatId, $msg);
    }

    private function handleCapacity(User $user, string $chatId): void
    {
        $cap = $this->capacity->calculateWeekly($user);
        $hours = round($cap['total_minutes'] / 60, 1);
        $capHours = round($cap['capacity_minutes'] / 60, 1);

        $icon = match ($cap['status']) {
            'overload' => '🚨',
            'padat' => '⚠️',
            default => '🟢',
        };

        $msg = "{$icon} <b>Kapasitas Minggu Ini: {$cap['percent']}%</b> ({$cap['status']})\n";
        $msg .= "Beban terencana: {$cap['total_minutes']} menit ({$hours} jam dari {$capHours} jam).\n";

        if ($cap['status'] === 'overload') {
            $msg .= "\nSaran tindakan: geser tenggat, jeda satu project, atau turunkan prioritas task.";
        }

        $this->client->sendMessage($chatId, $msg);
    }

    private function handleToday(User $user, string $chatId): void
    {
        $tz = $user->displayTimezone();
        $today = Carbon::now($tz);
        $todayStartUtc = $today->copy()->startOfDay()->utc();
        $todayEndUtc = $today->copy()->endOfDay()->utc();

        $schedules = Schedule::query()
            ->whereHas('task.project', fn ($q) => $q->where('user_id', $user->id))
            ->whereBetween('start_at', [$todayStartUtc, $todayEndUtc])
            ->with('task.project')
            ->orderBy('start_at')
            ->get();

        $overdueCount = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Waiting])
            ->where('due_date', '<', $today->toDateString())
            ->count();

        $cap = $this->capacity->calculateWeekly($user);

        $msg = "📅 <b>Hari Ini — " . $today->translatedFormat('l, d M Y') . "</b>\n\n";

        if ($schedules->isEmpty()) {
            $msg .= "Tidak ada jadwal spesifik untuk hari ini.\n";
        } else {
            foreach ($schedules as $s) {
                $time = $s->start_at->setTimezone($tz)->format('H:i') . '–' . $s->end_at->setTimezone($tz)->format('H:i');
                $statusMark = $s->task->status === TaskStatus::Done ? "✓" : "•";
                $msg .= "{$statusMark} <code>{$time}</code> " . htmlspecialchars($s->task->title) . " (<i>" . htmlspecialchars($s->task->project->name) . "</i>)\n";
            }
        }

        if ($overdueCount > 0) {
            $msg .= "\n⚠️ <b>{$overdueCount} task terlambat</b>";
        }

        $msg .= "\nKapasitas minggu: <b>{$cap['percent']}%</b> ({$cap['status']})";

        $this->client->sendMessage($chatId, $msg);
    }

    private function handleDone(User $user, string $chatId, string $taskIdStr): void
    {
        $id = (int) $taskIdStr;
        if ($id <= 0) {
            $this->client->sendMessage($chatId, "⚠️ Masukkan ID task. Contoh: <code>/done 5</code>");
            return;
        }

        $task = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id))
            ->find($id);

        if (! $task) {
            $this->client->sendMessage($chatId, "Task #{$id} tidak ditemukan.");
            return;
        }

        $this->tasks->changeStatus($task, TaskStatus::Done);

        $this->client->sendMessage($chatId, "✓ Task #{$id} \"<b>" . htmlspecialchars($task->title) . "</b>\" selesai!");
    }

    private function handleHelp(string $chatId): void
    {
        $msg = "🤖 <b>Perintah NARA Bot:</b>\n\n"
            . "• <code>/add &lt;teks&gt;</code> — Catat ke Inbox (Task)\n"
            . "• <code>/idea &lt;teks&gt;</code> — Simpan ide ke Inbox\n"
            . "• <i>Ketik teks biasa</i> — Otomatis disimpan ke Inbox\n"
            . "• <code>/inbox</code> — Tampilkan item menunggu\n"
            . "• <code>/today</code> — Jadwal hari ini & terlambat\n"
            . "• <code>/cap</code> — Persentase kapasitas minggu ini\n"
            . "• <code>/done &lt;id&gt;</code> — Tandai task selesai\n"
            . "• <code>/help</code> — Bantuan perintah";

        $this->client->sendMessage($chatId, $msg);
    }
}
