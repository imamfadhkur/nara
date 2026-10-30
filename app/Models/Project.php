<?php

namespace App\Models;

use App\Support\Enums\Level;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\WipSlot;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $goal_id
 * @property string $name
 * @property string|null $description
 * @property ProjectCategory $category
 * @property ProjectOrigin $origin
 * @property ProjectStatus $status
 * @property WipSlot|null $wip_slot
 * @property Level $impact
 * @property Level $urgency
 * @property Carbon|null $start_date
 * @property Carbon|null $due_date
 * @property int $progress_percent
 * @property string|null $definition_of_done
 * @property Carbon|null $last_activity_at
 * @property-read Goal|null $goal
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'goal_id', 'name', 'description', 'category', 'origin', 'status',
        'wip_slot', 'impact', 'urgency', 'start_date', 'due_date', 'progress_percent',
        'definition_of_done', 'last_activity_at',
    ];

    protected $attributes = [
        'category' => 'feature',
        'origin' => 'personal',
        'status' => 'idea',
        'impact' => 'medium',
        'urgency' => 'medium',
        'progress_percent' => 0,
    ];

    protected function casts(): array
    {
        return [
            'category' => ProjectCategory::class,
            'origin' => ProjectOrigin::class,
            'status' => ProjectStatus::class,
            'wip_slot' => WipSlot::class,
            'impact' => Level::class,
            'urgency' => Level::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'progress_percent' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Goal, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

