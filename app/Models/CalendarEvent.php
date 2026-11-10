<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Cermin event jadwal di Google Calendar (Bagian 4.3 & 6.3).
 *
 * @property int $id
 * @property int $schedule_id
 * @property string $google_calendar_id
 * @property string $google_event_id
 * @property string|null $etag
 * @property string $sync_status
 * @property Carbon|null $last_synced_at
 * @property string|null $last_error
 * @property int $retry_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Schedule $schedule
 */
class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'google_calendar_id',
        'google_event_id',
        'etag',
        'sync_status',
        'last_synced_at',
        'last_error',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Schedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}

