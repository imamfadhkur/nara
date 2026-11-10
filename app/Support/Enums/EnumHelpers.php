<?php

namespace App\Support\Enums;

/**
 * Helper bersama untuk PHP backed enum NARA (aturan struktur 3.4 no. 3).
 *
 * Enum yang memakai trait ini wajib mengimplementasikan label().
 */
trait EnumHelpers
{
    abstract public function label(): string;

    /** Warna token untuk StatusBadge (lihat resources/css/app.css). */
    public function tone(): string
    {
        return 'neutral';
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

