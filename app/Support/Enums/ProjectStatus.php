<?php

namespace App\Support\Enums;

/** Status project (Bagian 7.3). */
enum ProjectStatus: string
{
    use EnumHelpers;

    case Idea = 'idea';
    case Active = 'active';
    case Paused = 'paused';
    case Done = 'done';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Idea => 'Ide',
            self::Active => 'Aktif',
            self::Paused => 'Dijeda',
            self::Done => 'Selesai',
            self::Archived => 'Arsip',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Idea => 'accent',
            self::Active => 'primary',
            self::Paused => 'warning',
            self::Done => 'success',
            self::Archived => 'neutral',
        };
    }

    /** Project yang dihitung dalam WIP dan kapasitas (7.12). */
    public function countsTowardWip(): bool
    {
        return $this === self::Active;
    }
}

