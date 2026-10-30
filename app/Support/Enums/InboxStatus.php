<?php

namespace App\Support\Enums;

enum InboxStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Converted = 'converted';
    case Discarded = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Converted => 'Dikonversi',
            self::Discarded => 'Dibuang',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Converted => 'success',
            self::Discarded => 'muted',
        };
    }
}

