<?php

namespace App\Models;

use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Task tidak menyimpan impact/urgency/progress (D-10, Bagian 6.3).
 *
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property string|null $waiting_reason
 * @property TaskPriority $priority
 * @property int|null $estimated_minutes
 * @property int|null $actual_minutes
 * @property Carbon|null $due_date
 * @property Carbon|null $completed_at
 * @property int $sort_order
 * @property-read Project $project
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'title', 'description', 'status', 'waiting_reason', 'priority',
        'estimated_minutes', 'actual_minutes', 'due_date', 'completed_at', 'sort_order',
    ];

    protected $attributes = [
        'status' => 'todo',
        'priority' => 'normal',
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'estimated_minutes' => 'integer',
            'actual_minutes' => 'integer',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /** @return HasMany<TaskSubtask, $this> */
    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Terlambat: tenggat lewat (tanggal lokal pengguna) dan belum selesai/batal. */
    public function isOverdue(?string $timezone = null): bool
    {
        if (! $this->due_date || ! $this->status->isOpen()) {
            return false;
        }

        $today = now($timezone ?? config('nara.default_timezone'))->toDateString();

        return $this->due_date->toDateString() < $today;
    }
}

