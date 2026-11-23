<?php

namespace App\Services;

use App\Enums\BackupType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use SQLite3;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DatabaseBackupService
{
    private const LOCK_KEY = 'database-backup-spatie-operation';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->destination()
            ->backups()
            ->map(fn (Backup $backup): array => $this->meta($backup))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function create(?Authenticatable $user = null, BackupType $type = BackupType::Database): array
    {
        $result = Cache::lock(self::LOCK_KEY, 600)->block(
            10,
            fn (): array => $this->performCreate($user, $type)
        );

        if ($result === null) {
            throw new RuntimeException('Sedang ada proses backup/restore lain. Silakan coba lagi.');
        }

        return $result;
    }

    public function find(string $filename): Backup
    {
        $this->assertValidFilename($filename);

        $backup = $this->destination()
            ->backups()
            ->first(fn (Backup $item): bool => basename($item->path()) === $filename);

        if (! $backup) {
            throw new RuntimeException('Backup tidak ditemukan.');
        }

        return $backup;
    }

    public function download(string $filename): StreamedResponse
    {
        $backup = $this->find($filename);

        return Storage::disk($this->diskName())->download(
            $backup->path(),
            basename($backup->path()),
            ['Content-Type' => 'application/zip']
        );
    }

    public function delete(string $filename): void
    {
        $this->find($filename)->delete();
    }

    /**
     * @return array{backup: string, safety: string, driver: string}
     */
    public function restore(string $filename, ?Authenticatable $user = null): array
    {
        $result = Cache::lock(self::LOCK_KEY, 600)->block(
            10,
            function () use ($filename, $user): array {
                $backup = $this->find($filename);
                $dump = $this->dumpFromZip($backup);

                // Safety backup TANPA lock bertingkat.
                $safety = $this->performCreate($user, BackupType::Database);

                $connection = (string) config('database.default');
                $settings = config("database.connections.{$connection}") ?? [];

                if ($dump['driver'] === 'sqlite') {
                    $this->restoreSqlite($connection, $settings, $dump['sql']);
                } elseif (in_array($dump['driver'], ['mysql', 'mariadb'], true)) {
                    $this->restoreMysql($settings, $dump['sql']);
                } else {
                    throw new RuntimeException(
                        'Driver '.$dump['driver'].' belum didukung untuk restore otomatis.'
                    );
                }

                Log::info('Database restore selesai via Spatie', [
                    'backup' => $filename,
                    'driver' => $dump['driver'],
                    'safety_backup' => $safety['filename'],
                    'restored_by' => $user?->getAuthIdentifier(),
                ]);

                return [
                    'backup' => $filename,
                    'safety' => $safety['filename'],
                    'driver' => $dump['driver'],
                ];
            }
        );

        if ($result === null) {
            throw new RuntimeException('Sedang ada proses backup/restore lain. Silakan coba lagi.');
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function performCreate(?Authenticatable $user, BackupType $type): array
    {
        $before = $this->filenames();

        $arguments = [
            '--only-to-disk' => $this->diskName(),
            '--disable-notifications' => true,
        ];

        if ($type === BackupType::Database) {
            $arguments['--only-db'] = true;
        } else {
            $arguments['--filename-suffix'] = 'full';
            $arguments['--timeout'] = 600;
        }

        $status = Artisan::call('backup:run', $arguments);

        if ($status !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'backup:run gagal.');
        }

        $created = array_values(array_diff($this->filenames(), $before));
        $filename = $created[0] ?? $this->destination()->newestBackup()?->path();

        if (! $filename) {
            throw new RuntimeException('Backup selesai tetapi berkas tidak ditemukan.');
        }

        $backup = $this->find(basename($filename));

        Log::info('Backup dibuat via Spatie', [
            'backup' => basename($filename),
            'type' => $type->value,
            'created_by' => $user?->getAuthIdentifier(),
        ]);

        return $this->meta($backup);
    }

    /**
     * @return array<int, string>
     */
    private function filenames(): array
    {
        return $this->destination()
            ->backups()
            ->map(fn (Backup $backup): string => basename($backup->path()))
            ->values()
            ->all();
    }

    /**
     * @return array{sql: string, driver: string}
     */
    private function dumpFromZip(Backup $backup): array
    {
        $absolutePath = Storage::disk($this->diskName())->path($backup->path());

        $zip = new ZipArchive;
        $status = $zip->open($absolutePath);

        if ($status !== true) {
            throw new RuntimeException('Gagal membuka arsip backup. Kode: '.$status);
        }

        $password = config('backup.backup.password');
        if ($password) {
            $zip->setPassword((string) $password);
        }

        $entry = null;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name === false) {
                continue;
            }

            $base = strtolower(basename(str_replace('\\', '/', $name)));

            if (str_ends_with($base, '.sql')) {
                $entry = $name;

                break;
            }
        }

        if ($entry === null) {
            $zip->close();

            throw new RuntimeException('Tidak ada dump .sql di dalam arsip backup.');
        }

        $sql = $zip->getFromName($entry);
        $zip->close();

        if (! is_string($sql) || $sql === '') {
            throw new RuntimeException('Dump .sql kosong atau tidak dapat dibaca.');
        }

        $base = strtolower(basename(str_replace('\\', '/', $entry)));

        $driver = match (true) {
            str_starts_with($base, 'sqlite-') => 'sqlite',
            str_starts_with($base, 'mysql-') => 'mysql',
            str_starts_with($base, 'pgsql-') => 'pgsql',
            default => (string) config('database.default'),
        };

        return ['sql' => $sql, 'driver' => $driver];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function restoreSqlite(string $connection, array $settings, string $sql): void
    {
        $database = (string) ($settings['database'] ?? '');

        if ($database === '' || ! is_file($database)) {
            throw new RuntimeException('File database SQLite tujuan tidak ditemukan.');
        }

        $temporary = $database.'.restore-'.bin2hex(random_bytes(4));

        $sqlite = new SQLite3($temporary);
        $sqlite->busyTimeout(5000);
        $sqlite->exec('PRAGMA foreign_keys = OFF;');

        if (! $sqlite->exec($sql)) {
            $error = $sqlite->lastErrorMsg();
            $sqlite->close();
            @unlink($temporary);

            throw new RuntimeException('Gagal memulihkan SQLite: '.$error);
        }

        $sqlite->close();
        clearstatcache();

        if (! is_file($temporary) || filesize($temporary) === 0) {
            throw new RuntimeException('Hasil restore SQLite kosong.');
        }

        DB::purge($connection);

        @unlink($database.'-wal');
        @unlink($database.'-shm');

        if (! @unlink($database) || ! @rename($temporary, $database)) {
            @unlink($temporary);
            DB::purge($connection);

            throw new RuntimeException(
                'Gagal menimpa file database. Pastikan tidak ada proses lain yang mengunci database.'
            );
        }

        DB::purge($connection);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function restoreMysql(array $settings, string $sql): void
    {
        $binaryPath = rtrim((string) ($settings['dump']['dump_binary_path'] ?? ''), '/\\');
        $suffix = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
        $client = $binaryPath !== ''
            ? $binaryPath.DIRECTORY_SEPARATOR.'mysql'.$suffix
            : 'mysql'.$suffix;

        $result = Process::timeout((int) ($settings['dump']['timeout'] ?? 600))
            ->env(['MYSQL_PWD' => (string) ($settings['password'] ?? '')])
            ->input($sql)
            ->run([
                $client,
                '--host='.(string) ($settings['host'] ?? '127.0.0.1'),
                '--port='.(string) ($settings['port'] ?? 3306),
                '--user='.(string) ($settings['username'] ?? ''),
                (string) ($settings['database'] ?? ''),
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('Restore MySQL gagal: '.$result->errorOutput());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(Backup $backup): array
    {
        $type = BackupType::fromFilename(basename($backup->path()));

        return [
            'filename' => basename($backup->path()),
            'path' => $backup->path(),
            'disk' => $this->diskName(),
            'connection' => (string) config('database.default'),
            'type' => $type->value,
            'type_label' => $type->label(),
            'size_bytes' => (int) $backup->sizeInBytes(),
            'created_at' => $backup->date()->toIso8601String(),
        ];
    }

    private function destination(): BackupDestination
    {
        $destination = BackupDestination::create(
            $this->diskName(),
            (string) config('backup.backup.name', config('app.name'))
        );

        if ($destination->connectionError() !== null) {
            throw new RuntimeException(
                'Disk backup tidak dapat diakses: '.$destination->connectionError()?->getMessage()
            );
        }

        return $destination;
    }

    private function diskName(): string
    {
        return (string) (config('backup.backup.destination.disks')[0] ?? 'backups');
    }

    private function assertValidFilename(string $filename): void
    {
        if (! preg_match('/^[A-Za-z0-9_.-]+$/', $filename) || str_contains($filename, '..')) {
            throw new RuntimeException('Nama backup tidak valid.');
        }
    }
}
