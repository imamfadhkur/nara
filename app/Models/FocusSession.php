<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Catatan sesi fokus per task (Bagian 6.3 & 873).
 *
 * @property int $id
 * @property int $user_id
 * @property int $task_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property int $duration_minutes
 * @property string|null $note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Task $task
 */
class FocusSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

