<?php

namespace App\Models;

use App\Support\Enums\ActivityEvent;
use App\Support\Enums\ActivitySource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Log aktivitas polimorfik. Tidak pernah dihapus/diubah (Bagian 6.1).
 *
 * @property int $id
 * @property int $user_id
 * @property string $subject_type
 * @property int $subject_id
 * @property ActivityEvent $event
 * @property array<string, mixed>|null $before_value
 * @property array<string, mixed>|null $after_value
 * @property ActivitySource $source
 * @property \Illuminate\Support\Carbon $created_at
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'subject_type', 'subject_id', 'event', 'before_value', 'after_value', 'source', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event' => ActivityEvent::class,
            'source' => ActivitySource::class,
            'before_value' => 'array',
            'after_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }
}

