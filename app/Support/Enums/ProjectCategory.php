<?php

namespace App\Support\Enums;

/** Kategori project sebagai kolom biasa (D-19). */
enum ProjectCategory: string
{
    use EnumHelpers;

    case Feature = 'feature';
    case Improvement = 'improvement';
    case Bug = 'bug';
    case Research = 'research';
    case Learning = 'learning';
    case Admin = 'admin';
    case Personal = 'personal';
    case Client = 'client';
    case Idea = 'idea';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Feature => 'Fitur',
            self::Improvement => 'Perbaikan',
            self::Bug => 'Bug',
            self::Research => 'Riset',
            self::Learning => 'Belajar',
            self::Admin => 'Administrasi',
            self::Personal => 'Personal',
            self::Client => 'Klien',
            self::Idea => 'Ide',
            self::Maintenance => 'Pemeliharaan',
        };
    }
}

