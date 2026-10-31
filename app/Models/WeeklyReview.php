<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model ritual mingguan Weekly Review (Bagian 6.4, 7.13, 879).
 *
 * @property int $id
 * @property int $user_id
 * @property Carbon $week_start
 * @property array<int, string>|null $top_priorities
 * @property string|null $reflection
 * @property int $completed_task_count
 * @property int $completed_minutes
 * @property int $output_count
 * @property int $planned_load_percent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
class WeeklyReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_start',
        'top_priorities',
        'reflection',
        'completed_task_count',
        'completed_minutes',
        'output_count',
        'planned_load_percent',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'top_priorities' => 'array',
            'completed_task_count' => 'integer',
            'completed_minutes' => 'integer',
            'output_count' => 'integer',
            'planned_load_percent' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
