<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Cache blok sibuk dari kalender eksternal untuk ConflictDetector (Bagian 4.5 & 6.3).
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $google_calendar_id
 * @property string|null $google_event_id
 * @property string|null $title
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property Carbon $fetched_at
 * @property-read User $user
 */
class BusyBlock extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'google_calendar_id',
        'google_event_id',
        'title',
        'start_at',
        'end_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

