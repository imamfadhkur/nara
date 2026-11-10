<?php

namespace App\Support\Enums;

enum TaskPriority: string
{
    use EnumHelpers;

    case High = 'high';
    case Normal = 'normal';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High => 'Tinggi',
            self::Normal => 'Normal',
            self::Low => 'Rendah',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::High => 'danger',
            self::Normal => 'primary',
            self::Low => 'neutral',
        };
    }
}

