<?php

namespace App\Support\Enums;

/** Tingkat impact / urgency project (hanya di project, D-10). */
enum Level: string
{
    use EnumHelpers;

    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High => 'Tinggi',
            self::Medium => 'Sedang',
            self::Low => 'Rendah',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::High => 'danger',
            self::Medium => 'warning',
            self::Low => 'neutral',
        };
    }
}

