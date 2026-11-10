<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Log pengiriman notifikasi per saluran (Bagian 7.8 & 9.10).
 *
 * @property int $id
 * @property int $notification_id
 * @property string $channel
 * @property string $status
 * @property string|null $error
 * @property Carbon $sent_at
 * @property-read Notification $notification
 */
class NotificationLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'notification_id',
        'channel',
        'status',
        'error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Notification, $this> */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}

