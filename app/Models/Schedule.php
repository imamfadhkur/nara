<?php

namespace App\Models;

use App\Support\Enums\ActivitySource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Jadwal sesi task (Bagian 6.3 & 7.5).
 * Waktu selalu disimpan UTC (D-18).
 *
 * @property int $id
 * @property int $task_id
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property ActivitySource|string $source
 * @property string|null $note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Task $task
 */
class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id', 'start_at', 'end_at', 'source', 'note',
    ];

    protected $attributes = [
        'source' => 'nara',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return HasOne<CalendarEvent, $this> */
    public function calendarEvent(): HasOne
    {
        return $this->hasOne(CalendarEvent::class);
    }

    public function durationMinutes(): int
    {
        return (int) $this->start_at->diffInMinutes($this->end_at);
    }
}

