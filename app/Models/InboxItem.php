<?php

namespace App\Models;

use App\Support\Enums\ActivitySource;
use App\Support\Enums\InboxStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Inbox / Quick Capture item (Bagian 6.3 & 7.9).
 *
 * @property int $id
 * @property int $user_id
 * @property string $content
 * @property ActivitySource $source
 * @property string|null $hint
 * @property string|null $telegram_message_id
 * @property InboxStatus $status
 * @property string|null $converted_to_type
 * @property int|null $converted_to_id
 * @property Carbon|null $processed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class InboxItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'content', 'source', 'hint', 'telegram_message_id',
        'status', 'converted_to_type', 'converted_to_id', 'processed_at',
    ];

    protected $attributes = [
        'status' => 'pending',
        'source' => 'web',
    ];

    protected function casts(): array
    {
        return [
            'status' => InboxStatus::class,
            'source' => ActivitySource::class,
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
