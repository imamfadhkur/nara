<?php

namespace App\Domain\Capture;

use App\Domain\Activity\ActivityLogger;
use App\Domain\Goals\GoalService;
use App\Domain\Projects\ProjectService;
use App\Domain\Tasks\TaskService;
use App\Models\Goal;
use App\Models\InboxItem;
use App\Models\LifeDomain;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\ActivityEvent;
use App\Support\Enums\ActivitySource;
use App\Support\Enums\InboxStatus;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Facades\DB;

/**
 * Layanan Inbox & Triase Cepat (Bagian 7.9).
 * Katup pelepas: semua ide/tugas masuk dulu tanpa klasifikasi; triase dilakukan dalam ≤ 5 menit/hari.
 */
class InboxService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly TaskService $tasks,
        private readonly ProjectService $projects,
        private readonly GoalService $goals,
    ) {}

    public function capture(
        User $user,
        string $content,
        ActivitySource $source = ActivitySource::Web,
        ?string $hint = null,
        ?string $telegramMsgId = null,
    ): InboxItem {
        // Potong pesan sangat panjang (>1000 karakter) sesuai Bagian 8.6
        $trimmedContent = trim($content);
        if (mb_strlen($trimmedContent) > 1000) {
            $trimmedContent = mb_substr($trimmedContent, 0, 990) . '... (dipotong)';
        }

        // Cegah duplikasi webhook Telegram berdasarkan telegram_message_id
        if ($telegramMsgId) {
            $existing = InboxItem::where('user_id', $user->id)
                ->where('telegram_message_id', $telegramMsgId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $item = InboxItem::create([
            'user_id' => $user->id,
            'content' => $trimmedContent,
            'source' => $source,
            'hint' => $hint,
            'telegram_message_id' => $telegramMsgId,
            'status' => InboxStatus::Pending,
        ]);

        $this->logger->withSource($source, function () use ($item) {
            $this->logger->log($item, ActivityEvent::Created, null, ['content' => $item->content]);
        });

        return $item;
    }

    /**
     * Triase ke Task pada project tertentu.
     * @param array<string, mixed> $data
     */
    public function triageToTask(InboxItem $item, Project $project, array $data = []): Task
    {
        return DB::transaction(function () use ($item, $project, $data) {
            $title = $data['title'] ?? $item->content;
            $task = $this->tasks->create($project, [
                'title' => $title,
                'status' => $data['status'] ?? TaskStatus::Todo,
                'priority' => $data['priority'] ?? TaskPriority::Normal,
                'estimated_minutes' => $data['estimated_minutes'] ?? null,
                'due_date' => $data['due_date'] ?? null,
            ]);

            $item->update([
                'status' => InboxStatus::Converted,
                'converted_to_type' => 'task',
                'converted_to_id' => $task->id,
                'processed_at' => now(),
            ]);

            return $task;
        });
    }

    /**
     * Triase ke Project baru (status default: idea).
     * @param array<string, mixed> $data
     */
    public function triageToProject(InboxItem $item, User $user, array $data = []): Project
    {
        return DB::transaction(function () use ($item, $user, $data) {
            $name = $data['name'] ?? $item->content;
            $project = $this->projects->create($user, [
                'name' => $name,
                'status' => $data['status'] ?? ProjectStatus::Idea,
                'category' => $data['category'] ?? ProjectCategory::Idea,
                'origin' => $data['origin'] ?? ProjectOrigin::Personal,
            ]);

            $item->update([
                'status' => InboxStatus::Converted,
                'converted_to_type' => 'project',
                'converted_to_id' => $project->id,
                'processed_at' => now(),
            ]);

            return $project;
        });
    }

    /**
     * Triase ke Someday: disimpan sebagai project `idea` dengan tag `someday` (Bagian 7.9).
     */
    public function triageToSomeday(InboxItem $item, User $user, ?string $name = null): Project
    {
        return DB::transaction(function () use ($item, $user, $name) {
            $project = $this->projects->create($user, [
                'name' => $name ?? $item->content,
                'status' => ProjectStatus::Idea,
                'category' => ProjectCategory::Idea,
                'origin' => ProjectOrigin::Personal,
                'description' => 'Someday / Mungkin Suatu Hari (dari Inbox #' . $item->id . ')',
            ]);

            $item->update([
                'status' => InboxStatus::Converted,
                'converted_to_type' => 'project',
                'converted_to_id' => $project->id,
                'processed_at' => now(),
            ]);

            return $project;
        });
    }

    /**
     * Triase ke Goal.
     * @param array<string, mixed> $data
     */
    public function triageToGoal(InboxItem $item, LifeDomain $domain, array $data = []): Goal
    {
        return DB::transaction(function () use ($item, $domain, $data) {
            $title = $data['title'] ?? $item->content;
            $goal = $this->goals->createGoal($domain, [
                'title' => $title,
                'target_date' => $data['target_date'] ?? null,
            ]);

            $item->update([
                'status' => InboxStatus::Converted,
                'converted_to_type' => 'goal',
                'converted_to_id' => $goal->id,
                'processed_at' => now(),
            ]);

            return $goal;
        });
    }

    public function discard(InboxItem $item): void
    {
        $item->update([
            'status' => InboxStatus::Discarded,
            'processed_at' => now(),
        ]);
    }
}

