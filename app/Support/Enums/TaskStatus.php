<?php

namespace App\Support\Enums;

/** Status task (Bagian 7.1). `blocked` digabung ke `waiting` (D-09). */
enum TaskStatus: string
{
    use EnumHelpers;

    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'Todo',
            self::InProgress => 'Dikerjakan',
            self::Waiting => 'Menunggu',
            self::Done => 'Selesai',
            self::Cancelled => 'Batal',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Todo => 'neutral',
            self::InProgress => 'primary',
            self::Waiting => 'warning',
            self::Done => 'success',
            self::Cancelled => 'muted',
        };
    }

    /** Status yang masih terbuka (belum selesai/batal). */
    public function isOpen(): bool
    {
        return in_array($this, [self::Todo, self::InProgress, self::Waiting], true);
    }
}

