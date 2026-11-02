<?php

namespace App\Models;

use App\Support\Enums\GoalStatus;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $life_domain_id
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $target_date
 * @property int|null $wip_limit
 * @property GoalStatus $status
 * @property int $progress_percent
 * @property-read LifeDomain $lifeDomain
 */
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    protected $fillable = [
        'life_domain_id', 'title', 'description', 'target_date', 'status', 'progress_percent', 'wip_limit',
    ];

    protected $attributes = [
        'status' => 'active',
        'progress_percent' => 0,
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'status' => GoalStatus::class,
            'progress_percent' => 'integer',
            'wip_limit' => 'integer',
        ];
    }

    /** @return BelongsTo<LifeDomain, $this> */
    public function lifeDomain(): BelongsTo
    {
        return $this->belongsTo(LifeDomain::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasMany<Project, $this> */
    public function activeProjects(): HasMany
    {
        return $this->projects()->where('status', \App\Support\Enums\ProjectStatus::Active);
    }

    public function activeProjectsCount(): int
    {
        return $this->activeProjects()->count();
    }

    public function getEffectiveWipLimit(?User $user = null): int
    {
        if ($this->wip_limit !== null && $this->wip_limit > 0) {
            return (int) $this->wip_limit;
        }

        $effectiveUser = $user ?? $this->lifeDomain?->user;
        if ($effectiveUser) {
            return max(1, app(\App\Domain\Settings\SettingService::class)->int($effectiveUser, 'wip_limit_per_slot'));
        }

        return 3;
    }
}

