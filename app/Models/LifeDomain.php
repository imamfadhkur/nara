<?php

namespace App\Models;

use Database\Factories\LifeDomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $color
 * @property int $sort_order
 */
class LifeDomain extends Model
{
    /** @use HasFactory<LifeDomainFactory> */
    use HasFactory;

    /** Palet warna yang diizinkan (token di resources/css/app.css). */
    public const COLORS = [
        'blue' => 'Biru',
        'violet' => 'Violet',
        'cyan' => 'Cyan',
        'green' => 'Hijau',
        'amber' => 'Kuning',
        'rose' => 'Merah muda',
        'slate' => 'Abu',
    ];

    protected $fillable = ['user_id', 'name', 'color', 'sort_order'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Goal, $this> */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function colorLabel(): string
    {
        return self::COLORS[$this->color] ?? $this->color;
    }
}

