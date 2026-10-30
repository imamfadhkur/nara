<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengaturan per pengguna (kunci di Bagian 6.7).
 *
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property mixed $value
 */
class Setting extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

