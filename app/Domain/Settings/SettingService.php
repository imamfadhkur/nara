<?php

namespace App\Domain\Settings;

use App\Models\Setting;
use App\Models\User;
use InvalidArgumentException;

/**
 * Akses kunci `settings` per pengguna dengan default dari config/nara.php (Bagian 6.7).
 */
class SettingService
{
    /** @var array<int, array<string, mixed>> */
    private array $cache = [];

    public function get(User $user, string $key): mixed
    {
        return $this->all($user)[$key] ?? throw new InvalidArgumentException("Kunci setting tidak dikenal: {$key}");
    }

    public function int(User $user, string $key): int
    {
        return (int) $this->get($user, $key);
    }

    /** @return array<string, mixed> */
    public function all(User $user): array
    {
        if (! isset($this->cache[$user->id])) {
            $stored = Setting::query()
                ->where('user_id', $user->id)
                ->pluck('value', 'key')
                ->all();

            $this->cache[$user->id] = array_merge(self::defaults(), $stored);
        }

        return $this->cache[$user->id];
    }

    public function set(User $user, string $key, mixed $value): void
    {
        if (! array_key_exists($key, self::defaults())) {
            throw new InvalidArgumentException("Kunci setting tidak dikenal: {$key}");
        }

        Setting::query()->updateOrCreate(
            ['user_id' => $user->id, 'key' => $key],
            ['value' => $value],
        );

        unset($this->cache[$user->id]);
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return config('nara.settings', []);
    }
}

