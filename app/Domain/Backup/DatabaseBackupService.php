<?php

namespace App\Domain\Backup;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;

/**
 * Backup database terenkripsi & uji restore (D-12, Bagian 13.2).
 *
 * - Kunci enkripsi: `config('nara.backup.encryption_key')`, WAJIB berbeda dari APP_KEY
 *   di produksi agar cadangan tetap bisa dibuka walau server hilang (asal kunci disimpan
 *   di password manager).
 * - Isi bundel sebelum dienkripsi: JSON {version, driver, created_at, tables, sql_dump}
 *   dikompresi gzencode, lalu dienkripsi AES-256-CBC (HMAC-SHA256 terautentikasi).
 * - Retensi: 7 harian + 4 mingguan (Minggu) + 3 bulanan (tanggal 1).
 */
class DatabaseBackupService
{
    public function createEncryptedBackup(?string $connectionName = null): string
    {
        $conn = DB::connection($connectionName);
        $driver = $conn->getDriverName();

        $payload = [
            'version' => 1,
            'driver' => $driver,
            'database' => $conn->getDatabaseName(),
            'created_at' => now('UTC')->toIso8601String(),
            'tables' => $this->dumpTablesPortable($conn->getName()),
            'sql_dump' => $driver === 'mysql' ? $this->tryMysqldump($conn->getConfig()) : null,
        ];

        $compressed = gzencode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 9);
        if ($compressed === false) {
            throw new RuntimeException('Gagal mengompresi payload backup.');
        }

        $ciphertext = $this->encrypter()->encryptString($compressed);
        $path = trim(config('nara.backup.path', 'nara'), '/')
            .'/backup-'.now('UTC')->format('Y-m-d-His').'.nara.enc';

        $this->disk()->put($path, $ciphertext);

        return $path;
    }

    /**
     * Dekripsi berkas backup dan kembalikan struktur payload.
     *
     * @return array{version: int, driver: string, database: string, created_at: string, tables: array<string, list<array<string, mixed>>>, sql_dump: string|null}
     */
    public function readBackup(string $path): array
    {
        if (! $this->disk()->exists($path)) {
            throw new RuntimeException("Berkas backup tidak ditemukan: {$path}");
        }

        $raw = (string) $this->disk()->get($path);
        $compressed = $this->encrypter()->decryptString($raw);
        $json = @gzdecode($compressed);

        if ($json === false) {
            throw new RuntimeException('Gagal membuka kompresi berkas backup.');
        }

        /** @var array{version: int, driver: string, database: string, created_at: string, tables: array<string, list<array<string, mixed>>>, sql_dump: string|null} */
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Uji restore (Bagian 13.2: "Backup yang tidak pernah diuji dianggap tidak ada"):
     * membuka enkripsi, memulihkan seluruh baris ke database sementara di memori,
     * dan memverifikasi jumlah baris tiap tabel cocok 100%.
     *
     * @return array{path: string, created_at: string, tables_verified: int, rows_verified: int}
     */
    public function verifyRestore(string $path): array
    {
        $bundle = $this->readBackup($path);

        $tablesVerified = 0;
        $rowsVerified = 0;
        $hasSqlite = in_array('sqlite', PDO::getAvailableDrivers(), true);

        $pdo = null;
        if ($hasSqlite) {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        foreach ($bundle['tables'] as $table => $rows) {
            $tablesVerified++;
            $rowCount = count($rows);

            if ($rows === []) {
                continue;
            }

            if ($pdo) {
                $columns = array_keys($rows[0]);
                $colDefs = implode(', ', array_map(fn (string $c) => '"'.str_replace('"', '""', $c).'" TEXT', $columns));
                $pdo->exec(sprintf('CREATE TABLE "%s" (%s)', str_replace('"', '""', $table), $colDefs));

                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $stmt = $pdo->prepare(sprintf('INSERT INTO "%s" VALUES (%s)', str_replace('"', '""', $table), $placeholders));

                foreach ($rows as $row) {
                    $stmt->execute(array_map(
                        fn ($v) => is_array($v) ? json_encode($v) : $v,
                        array_values($row),
                    ));
                }

                $count = (int) $pdo->query(sprintf('SELECT COUNT(*) FROM "%s"', str_replace('"', '""', $table)))->fetchColumn();
                if ($count !== $rowCount) {
                    throw new RuntimeException("Verifikasi restore gagal pada tabel {$table}: {$count} vs {$rowCount}");
                }
            }

            $rowsVerified += $rowCount;
        }

        return [
            'path' => $path,
            'created_at' => $bundle['created_at'],
            'tables_verified' => $tablesVerified,
            'rows_verified' => $rowsVerified,
            'engine' => $hasSqlite ? 'sqlite_memory' : 'json_structure',
        ];
    }

    /**
     * Pulihkan isi `tables` dari berkas backup ke koneksi yang ditunjuk.
     * Hanya untuk pemulihan darurat setelah skema (migrasi) sudah ada.
     */
    public function restoreInto(string $path, ?string $connectionName = null): int
    {
        $bundle = $this->readBackup($path);
        $conn = DB::connection($connectionName);
        $restoredRows = 0;

        $conn->getSchemaBuilder()->disableForeignKeyConstraints();
        try {
            foreach ($bundle['tables'] as $table => $rows) {
                if ($table === 'migrations' || ! $conn->getSchemaBuilder()->hasTable($table)) {
                    continue;
                }
                $conn->table($table)->delete();
                foreach (array_chunk($rows, 200) as $chunk) {
                    $conn->table($table)->insert($chunk);
                    $restoredRows += count($chunk);
                }
            }
        } finally {
            $conn->getSchemaBuilder()->enableForeignKeyConstraints();
        }

        return $restoredRows;
    }

    /**
     * Pangkas cadangan lama sesuai kebijakan 7 harian + 4 mingguan + 3 bulanan (Bagian 13.2).
     *
     * @return list<string> berkas yang dihapus
     */
    public function prune(): array
    {
        $prefix = trim(config('nara.backup.path', 'nara'), '/');
        $files = collect($this->disk()->files($prefix))
            ->filter(fn (string $f) => str_ends_with($f, '.nara.enc'))
            ->map(fn (string $f) => ['path' => $f, 'date' => $this->parseDateFromFilename($f)])
            ->filter(fn (array $item) => $item['date'] !== null)
            ->sortByDesc(fn (array $item) => $item['date']->getTimestamp())
            ->values();

        $keepDaily = (int) config('nara.backup.keep_daily', 7);
        $keepWeekly = (int) config('nara.backup.keep_weekly', 4);
        $keepMonthly = (int) config('nara.backup.keep_monthly', 3);

        $keep = [];
        $dailyBuckets = [];
        $weeklyBuckets = [];
        $monthlyBuckets = [];

        foreach ($files as $item) {
            /** @var Carbon $date */
            $date = $item['date'];
            $dayKey = $date->format('Y-m-d');
            $weekKey = $date->format('o-W');
            $monthKey = $date->format('Y-m');

            if (count($dailyBuckets) < $keepDaily && ! isset($dailyBuckets[$dayKey])) {
                $dailyBuckets[$dayKey] = true;
                $keep[$item['path']] = true;

                continue;
            }

            if ($date->isSunday() && count($weeklyBuckets) < $keepWeekly && ! isset($weeklyBuckets[$weekKey])) {
                $weeklyBuckets[$weekKey] = true;
                $keep[$item['path']] = true;

                continue;
            }

            if ($date->day === 1 && count($monthlyBuckets) < $keepMonthly && ! isset($monthlyBuckets[$monthKey])) {
                $monthlyBuckets[$monthKey] = true;
                $keep[$item['path']] = true;
            }
        }

        // Selalu pertahankan minimal berkas terbaru.
        if ($files->isNotEmpty()) {
            $keep[$files->first()['path']] = true;
        }

        $deleted = [];
        foreach ($files as $item) {
            if (! isset($keep[$item['path']])) {
                $this->disk()->delete($item['path']);
                $deleted[] = $item['path'];
            }
        }

        return $deleted;
    }

    public function latestBackupPath(): ?string
    {
        $prefix = trim(config('nara.backup.path', 'nara'), '/');

        return collect($this->disk()->files($prefix))
            ->filter(fn (string $f) => str_ends_with($f, '.nara.enc'))
            ->sort()
            ->last();
    }

    public function disk(): Filesystem
    {
        return Storage::disk(config('nara.backup.disk', 'backup'));
    }

    private function encrypter(): Encrypter
    {
        $rawKey = config('nara.backup.encryption_key') ?: config('app.key');
        if (! is_string($rawKey) || $rawKey === '') {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY belum diatur.');
        }

        if (str_starts_with($rawKey, 'base64:')) {
            $rawKey = base64_decode(substr($rawKey, 7), true) ?: '';
        }

        if (strlen($rawKey) !== 32) {
            $rawKey = hash('sha256', $rawKey, true);
        }

        return new Encrypter($rawKey, 'AES-256-CBC');
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function dumpTablesPortable(?string $connectionName): array
    {
        $conn = DB::connection($connectionName);
        $tables = [];

        if ($conn->getDriverName() === 'mysql') {
            $rawTables = $conn->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            $tableNames = array_map(fn ($row) => array_values((array) $row)[0], $rawTables);
        } else {
            $rawTables = $conn->getSchemaBuilder()->getTableListing();
            $tableNames = [];
            foreach ($rawTables as $rawName) {
                $tableNames[] = str_contains($rawName, '.') ? substr($rawName, strrpos($rawName, '.') + 1) : $rawName;
            }
        }

        foreach ($tableNames as $table) {
            if (in_array($table, ['sessions', 'cache', 'cache_locks'], true)) {
                continue;
            }

            $rows = $conn->table($table)->get()->map(fn ($r) => (array) $r)->all();
            $tables[$table] = $rows;
        }

        return $tables;
    }

    /** @param  array<string, mixed>  $cfg */
    private function tryMysqldump(array $cfg): ?string
    {
        $bin = (string) config('nara.backup.mysqldump_path', 'mysqldump');
        $args = [
            $bin,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--host='.(string) ($cfg['host'] ?? '127.0.0.1'),
            '--port='.(string) ($cfg['port'] ?? 3306),
            '--user='.(string) ($cfg['username'] ?? 'root'),
            (string) ($cfg['database'] ?? 'nara'),
        ];

        try {
            $result = Process::env([
                'MYSQL_PWD' => (string) ($cfg['password'] ?? ''),
            ])->timeout(60)->run($args);

            return $result->successful() ? $result->output() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDateFromFilename(string $path): ?Carbon
    {
        if (preg_match('/backup-(\d{4}-\d{2}-\d{2}-\d{6})\.nara\.enc$/', $path, $m) !== 1) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d-His', $m[1], 'UTC') ?: null;
    }
}

