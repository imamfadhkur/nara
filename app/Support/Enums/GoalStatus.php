<?php

namespace App\Support\Enums;

enum GoalStatus: string
{
    use EnumHelpers;

    case Active = 'active';
    case Achieved = 'achieved';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Achieved => 'Tercapai',
            self::Abandoned => 'Ditinggalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'primary',
            self::Achieved => 'success',
            self::Abandoned => 'neutral',
        };
    }
}

