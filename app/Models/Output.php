<?php

namespace App\Models;

use App\Support\Enums\OutputType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Catatan hasil karya nyata (Bagian 6.3 & 7.10).
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $project_id
 * @property int|null $task_id
 * @property string $title
 * @property OutputType $output_type
 * @property string|null $evidence_url
 * @property Carbon $produced_at
 * @property string|null $note
 * @property Carbon|null $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Project|null $project
 * @property-read Task|null $task
 */
class Output extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'project_id', 'task_id', 'title', 'output_type',
        'evidence_url', 'produced_at', 'note',
    ];

    protected $attributes = [
        'output_type' => 'other',
    ];

    protected function casts(): array
    {
        return [
            'output_type' => OutputType::class,
            'produced_at' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class)->withTrashed();
    }

    /** @return HasMany<OutputFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(OutputFile::class);
    }
}

