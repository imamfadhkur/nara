<?php

namespace App\Models;

use App\Domain\Auth\TwoFactorAuthenticator;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'telegram_chat_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Rahasia 2FA terenkripsi (D-12, Bagian 13.1).
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** @return HasMany<LifeDomain, $this> */
    public function lifeDomains(): HasMany
    {
        return $this->hasMany(LifeDomain::class)->orderBy('sort_order');
    }

    /** @return HasManyThrough<Goal, LifeDomain, $this> */
    public function goals(): HasManyThrough
    {
        return $this->hasManyThrough(Goal::class, LifeDomain::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** @return HasManyThrough<Task, Project, $this> */
    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Project::class);
    }

    /** @return HasMany<Setting, $this> */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    /** @return HasMany<InboxItem, $this> */
    public function inboxItems(): HasMany
    {
        return $this->hasMany(InboxItem::class)->orderByDesc('created_at');
    }

    /** @return HasMany<Output, $this> */
    public function outputs(): HasMany
    {
        return $this->hasMany(Output::class)->orderByDesc('produced_at');
    }

    /** @return HasMany<Notification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /** Verifikasi kode TOTP atau kode pemulihan. */
    public function verifyTwoFactorCode(string $code): bool
    {
        return app(TwoFactorAuthenticator::class)->verify($this, $code);
    }

    public function displayTimezone(): string
    {
        return $this->timezone ?: config('nara.default_timezone');
    }
}
