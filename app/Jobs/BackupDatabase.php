<?php

namespace App\Jobs;

use App\Domain\Backup\DatabaseBackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Job backup harian (Bagian 3.4 & 13.2). */
class BackupDatabase implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(DatabaseBackupService $backups): void
    {
        $path = $backups->createEncryptedBackup();
        $backups->verifyRestore($path);
        $backups->prune();
    }
}

