<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subtask: butir checklist tanpa status sendiri (Bagian 1.4).
 *
 * @property int $id
 * @property int $task_id
 * @property string $title
 * @property bool $is_done
 * @property int $sort_order
 * @property-read Task $task
 */
class TaskSubtask extends Model
{
    protected $fillable = ['task_id', 'title', 'is_done', 'sort_order'];

    protected $attributes = [
        'is_done' => false,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

