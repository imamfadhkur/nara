<?php

namespace App\Support\Enums;

/** Slot WIP project aktif (Bagian 7.3). */
enum WipSlot: string
{
    use EnumHelpers;

    case Academic = 'academic';
    case Business = 'business';
    case Personal = 'personal';

    public function label(): string
    {
        return match ($this) {
            self::Academic => 'Akademik',
            self::Business => 'Bisnis',
            self::Personal => 'Personal',
        };
    }
}

