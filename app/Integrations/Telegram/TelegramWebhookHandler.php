<?php

namespace App\Integrations\Telegram;

use App\Domain\Capture\InboxService;
use App\Domain\Integrations\IntegrationService;
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
        private readonly IntegrationService $integrations,
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

        // 1b. Aturan Integrasi Aktif/Nonaktif (Fase 2)
        if (! $this->integrations->isTelegramEnabled($user)) {
            Log::info("Telegram webhook diabaikan: integrasi Telegram dinonaktifkan di pengaturan untuk user {$user->id}.");
            $this->client->sendMessage($chatId, "⚠️ Integrasi Telegram saat ini sedang dinonaktifkan di menu Pengaturan NARA.");
            return;
        }

        // 2. Cegah duplikasi webhook (Bagian 8.6)
        if ($messageId !== '' && InboxItem::where('user_id', $user->id)->where('telegram_message_id', $messageId)->exists()) {
            return;
        }

        // 3. Routing Perintah
        if (str_starts_with($text, '/')) {
            // Dukungan klik cepat /done_123
            if (preg_match('/^\/done_(\d+)$/i', $text, $matches)) {
                $this->handleDone($user, $chatId, $matches[1]);
                return;
            }

            $parts = explode(' ', $text, 2);
            $command = strtolower($parts[0]);
            $argument = trim($parts[1] ?? '');

            match ($command) {
                '/add' => $this->handleAdd($user, $chatId, $argument, $messageId),
                '/idea' => $this->handleIdea($user, $chatId, $argument, $messageId),
                '/inbox' => $this->handleInbox($user, $chatId),
                '/tasks' => $this->handleTasks($user, $chatId),
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

        $overdueTasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Waiting])
            ->where('due_date', '<', $today->toDateString())
            ->with('project')
            ->orderBy('due_date')
            ->get();

        $cap = $this->capacity->calculateWeekly($user);

        $msg = "📅 <b>Hari Ini — " . $today->translatedFormat('l, d M Y') . "</b>\n\n";

        if ($schedules->isEmpty()) {
            $msg .= "Tidak ada jadwal spesifik untuk hari ini.\n";
        } else {
            foreach ($schedules as $s) {
                $time = $s->start_at->setTimezone($tz)->format('H:i') . '–' . $s->end_at->setTimezone($tz)->format('H:i');
                if ($s->task->status === TaskStatus::Done) {
                    $msg .= "✓ <code>{$time}</code> <s>" . htmlspecialchars($s->task->title) . "</s> (#{$s->task->id})\n";
                } else {
                    $msg .= "• <code>{$time}</code> <b>" . htmlspecialchars($s->task->title) . "</b> (#{$s->task->id})\n"
                         .  "   <i>" . htmlspecialchars($s->task->project->name) . "</i> — selesai: /done_{$s->task->id}\n";
                }
            }
        }

        if ($overdueTasks->isNotEmpty()) {
            $msg .= "\n⚠️ <b>Task Terlambat (" . $overdueTasks->count() . "):</b>\n";
            foreach ($overdueTasks->take(5) as $ot) {
                $msg .= "• <b>#{$ot->id}</b> " . htmlspecialchars($ot->title) . " — /done_{$ot->id}\n";
            }
            if ($overdueTasks->count() > 5) {
                $msg .= "<i>...dan " . ($overdueTasks->count() - 5) . " lainnya (ketik /tasks).</i>\n";
            }
        }

        $msg .= "\nKapasitas minggu: <b>{$cap['percent']}%</b> ({$cap['status']})";

        $this->client->sendMessage($chatId, $msg);
    }

    private function handleTasks(User $user, string $chatId): void
    {
        $tasks = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->whereIn('status', [TaskStatus::InProgress, TaskStatus::Todo])
            ->with('project')
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->limit(10)
            ->get();

        if ($tasks->isEmpty()) {
            $this->client->sendMessage($chatId, "Tidak ada task aktif yang perlu dikerjakan.");
            return;
        }

        $msg = "📋 <b>Daftar Task Aktif:</b>\n\n";
        foreach ($tasks as $t) {
            $icon = $t->status === TaskStatus::InProgress ? "⏳" : "☐";
            $dueInfo = $t->due_date ? " [tenggat: " . $t->due_date->format('d/m') . "]" : "";
            $msg .= "{$icon} <b>#{$t->id}</b> " . htmlspecialchars($t->title) . "{$dueInfo}\n"
                 .  "   <i>" . htmlspecialchars($t->project->name) . "</i> — selesai: /done_{$t->id}\n\n";
        }

        $msg .= "<i>Ketuk /done_&lt;id&gt; untuk langsung menyelesaikan.</i>";

        $this->client->sendMessage($chatId, $msg);
    }

    private function handleDone(User $user, string $chatId, string $argument): void
    {
        // 1. Tanpa argumen: tampilkan task aktif yang siap diselesaikan
        if ($argument === '') {
            $activeTasks = Task::query()
                ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
                ->whereIn('status', [TaskStatus::InProgress, TaskStatus::Todo])
                ->with('project')
                ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
                ->orderByRaw('due_date IS NULL, due_date ASC')
                ->limit(6)
                ->get();

            if ($activeTasks->isEmpty()) {
                $this->client->sendMessage($chatId, "Tidak ada task aktif yang bisa diselesaikan saat ini.");
                return;
            }

            $msg = "📋 <b>Pilih task yang ingin diselesaikan:</b>\n\n";
            foreach ($activeTasks as $at) {
                $icon = $at->status === TaskStatus::InProgress ? "⏳" : "☐";
                $msg .= "{$icon} <b>#{$at->id}</b> " . htmlspecialchars($at->title) . "\n"
                     .  "   ↳ Ketuk: /done_{$at->id}\n\n";
            }
            $msg .= "<i>Ketik /tasks untuk melihat seluruh daftar task aktif.</i>";

            $this->client->sendMessage($chatId, $msg);
            return;
        }

        // 2. Argumen bukan angka: pencarian berdasarkan judul task
        if (! is_numeric($argument)) {
            $matched = Task::query()
                ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
                ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Waiting])
                ->where('title', 'like', '%' . $argument . '%')
                ->with('project')
                ->limit(5)
                ->get();

            if ($matched->isEmpty()) {
                $this->client->sendMessage($chatId, "⚠️ Tidak ditemukan task aktif dengan kata kunci: <i>" . htmlspecialchars($argument) . "</i>.\nKetik /tasks untuk melihat daftar.");
                return;
            }

            if ($matched->count() === 1) {
                $task = $matched->first();
                $this->tasks->changeStatus($task, TaskStatus::Done);
                $this->client->sendMessage($chatId, "✓ Task #{$task->id} \"<b>" . htmlspecialchars($task->title) . "</b>\" selesai!");
                return;
            }

            $msg = "🔍 <b>Ditemukan beberapa task dengan kata kunci \"{$argument}\":</b>\n\n";
            foreach ($matched as $m) {
                $msg .= "• <b>#{$m->id}</b> " . htmlspecialchars($m->title) . "\n"
                     .  "   ↳ Ketuk: /done_{$m->id}\n";
            }
            $this->client->sendMessage($chatId, $msg);
            return;
        }

        // 3. Argumen angka (ID task langsung)
        $id = (int) $argument;
        $task = Task::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', $user->id)->whereNull('deleted_at'))
            ->find($id);

        if (! $task) {
            $this->client->sendMessage($chatId, "Task #{$id} tidak ditemukan. Ketik /tasks untuk melihat daftar task aktif.");
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
            . "• <code>/today</code> — Jadwal hari ini & task terlambat (lengkap dengan ID)\n"
            . "• <code>/tasks</code> — Daftar seluruh task aktif & ID-nya\n"
            . "• <code>/cap</code> — Persentase kapasitas minggu ini\n"
            . "• <code>/done</code> — Tampilkan pilihan task aktif untuk diselesaikan\n"
            . "• <code>/done &lt;id atau judul&gt;</code> — Selesaikan task (misal: <code>/done 5</code> atau <code>/done bug</code>)\n"
            . "• <code>/help</code> — Bantuan perintah";

        $this->client->sendMessage($chatId, $msg);
    }
}

