<?php

namespace App\Console\Commands;

use App\Domain\Backup\DatabaseBackupService;
use Illuminate\Console\Command;

/**
 * Perintah backup & uji restore (Bagian 10 Fase 0 & Bagian 13.2).
 *
 * Contoh:
 *   php artisan nara:backup                  # buat backup terenkripsi + verifikasi + prune
 *   php artisan nara:backup --verify-only    # uji restore berkas backup terbaru
 *   php artisan nara:backup --restore=path   # pulihkan ke database aktif (darurat)
 */
class BackupCommand extends Command
{
    protected $signature = 'nara:backup
        {--verify-only : Hanya jalankan uji restore pada berkas backup terbaru}
        {--file= : Path berkas backup tertentu untuk diverifikasi/dipulihkan}
        {--restore : Pulihkan isi berkas ke database aktif (hati-hati)}';

    protected $description = 'Buat backup database NARA terenkripsi dan jalankan uji restore';

    public function handle(DatabaseBackupService $backups): int
    {
        if ($this->option('restore')) {
            $target = (string) ($this->option('file') ?: $backups->latestBackupPath());
            if ($target === '') {
                $this->error('Tidak ada berkas backup yang ditemukan.');

                return self::FAILURE;
            }

            $rows = $backups->restoreInto($target);
            $this->info("Pemulihan selesai dari [{$target}]: {$rows} baris dipulihkan.");

            return self::SUCCESS;
        }

        if ($this->option('verify-only')) {
            $target = (string) ($this->option('file') ?: $backups->latestBackupPath());
            if ($target === '') {
                $this->error('Belum ada berkas backup untuk diuji.');

                return self::FAILURE;
            }

            $report = $backups->verifyRestore($target);
            $this->info(sprintf(
                'Uji restore BERHASIL: %s (%d tabel, %d baris terverifikasi).',
                $report['path'],
                $report['tables_verified'],
                $report['rows_verified'],
            ));

            return self::SUCCESS;
        }

        $path = $backups->createEncryptedBackup();
        $report = $backups->verifyRestore($path);
        $pruned = $backups->prune();

        $this->info(sprintf(
            'Backup terenkripsi dibuat & terverifikasi: %s (%d tabel, %d baris; %d arsip lama dibersihkan).',
            $path,
            $report['tables_verified'],
            $report['rows_verified'],
            count($pruned),
        ));

        return self::SUCCESS;
    }
}

