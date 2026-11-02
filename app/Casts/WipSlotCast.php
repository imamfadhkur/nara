<?php

namespace App\Casts;

use App\Models\Goal;
use App\Support\Enums\WipSlot as WipSlotEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class WipSlotCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // 1. Cek apakah cocok dengan enum standar baseline
        if ($enum = WipSlotEnum::tryFrom($value)) {
            return $enum;
        }

        // 2. Jika berbentuk 'goal_X' atau merujuk ke Goal
        if (is_string($value) && str_starts_with($value, 'goal_')) {
            $goalId = (int) substr($value, 5);
            $goalTitle = null;

            if ($model->relationLoaded('goal') && $model->goal?->id === $goalId) {
                $goalTitle = $model->goal->title;
            } else {
                $goal = Goal::find($goalId);
                $goalTitle = $goal?->title;
            }

            if ($goalTitle) {
                return new class($value, $goalTitle) {
                    public function __construct(public string $value, public string $title) {}
                    public function label(): string { return $this->title; }
                    public function icon(): string { return '🎯'; }
                    public function __toString(): string { return $this->value; }
                };
            }
        }

        // 3. Fallback dinamis representatif
        return new class($value) {
            public function __construct(public string $value) {}
            public function label(): string { return ucfirst(str_replace(['_', '-'], ' ', $this->value)); }
            public function icon(): string { return '📁'; }
            public function __toString(): string { return $this->value; }
        };
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof WipSlotEnum) {
            return $value->value;
        }

        if (is_object($value) && isset($value->value)) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
