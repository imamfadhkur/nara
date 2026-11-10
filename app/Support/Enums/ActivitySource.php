<?php

namespace App\Support\Enums;

/** Asal perubahan data untuk activity_logs (Bagian 6.3). */
enum ActivitySource: string
{
    use EnumHelpers;

    case Web = 'web';
    case Telegram = 'telegram';
    case Google = 'google';
    case System = 'system';
    case Ai = 'ai';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::Telegram => 'Telegram',
            self::Google => 'Google',
            self::System => 'Sistem',
            self::Ai => 'AI',
        };
    }
}

