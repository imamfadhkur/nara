<?php

namespace Tests\Feature;

use App\Domain\Backup\DatabaseBackupService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_and_restore_verification(): void
    {
        Storage::fake('backup');
        config(['nara.backup.disk' => 'backup']);

        User::factory()->count(3)->create();

        $service = app(DatabaseBackupService::class);
        $path = $service->createEncryptedBackup();

        $this->assertNotEmpty($path);
        Storage::disk('backup')->assertExists($path);

        // Uji verifikasi restore (membongkar enkripsi dan memeriksa integritas data)
        $report = $service->verifyRestore($path);

        $this->assertSame($path, $report['path']);
        $this->assertGreaterThan(0, $report['tables_verified']);
        $this->assertGreaterThanOrEqual(3, $report['rows_verified']);
    }

    public function test_nara_backup_command_runs_successfully(): void
    {
        Storage::fake('backup');
        config(['nara.backup.disk' => 'backup']);

        User::factory()->create();

        $this->artisan('nara:backup')
            ->expectsOutputToContain('Backup terenkripsi dibuat & terverifikasi')
            ->assertSuccessful();

        $this->artisan('nara:backup --verify-only')
            ->expectsOutputToContain('Uji restore BERHASIL')
            ->assertSuccessful();
    }
}

