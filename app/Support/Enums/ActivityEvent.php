<?php

namespace App\Support\Enums;

/** Jenis event activity_logs (Bagian 6.3 & 6.9). */
enum ActivityEvent: string
{
    use EnumHelpers;

    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case StatusChanged = 'status_changed';
    case Completed = 'completed';
    case WipChanged = 'wip_changed';
    case Scheduled = 'scheduled';
    case OutputAdded = 'output_added';
    case SyncFailed = 'sync_failed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'dibuat',
            self::Updated => 'diubah',
            self::Deleted => 'dihapus',
            self::StatusChanged => 'status berubah',
            self::Completed => 'selesai',
            self::WipChanged => 'slot WIP berubah',
            self::Scheduled => 'dijadwalkan',
            self::OutputAdded => 'output ditambahkan',
            self::SyncFailed => 'sinkron gagal',
        };
    }
}

