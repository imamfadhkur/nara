<?php

namespace App\Support\Enums;

enum ProjectOrigin: string
{
    use EnumHelpers;

    case Personal = 'personal';
    case External = 'external';
    case Freelance = 'freelance';
    case Research = 'research';
    case Academic = 'academic';

    public function label(): string
    {
        return match ($this) {
            self::Personal => 'Personal',
            self::External => 'Eksternal',
            self::Freelance => 'Freelance',
            self::Research => 'Riset',
            self::Academic => 'Akademik',
        };
    }
}

