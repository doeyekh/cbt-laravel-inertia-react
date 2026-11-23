<?php

namespace App\Console\Commands;

use App\Enums\BackupType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('db:backup {--type=database : Jenis backup: database atau full}')]
#[Description('Membuat backup database atau database + file via Spatie Laravel Backup')]
class BackupDatabaseCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = BackupType::tryFrom((string) $this->option('type'));

        if ($type === null) {
            $this->error('Jenis backup harus database atau full.');

            return self::FAILURE;
        }

        $arguments = ['--disable-notifications' => true];

        if ($type === BackupType::Database) {
            $arguments['--only-db'] = true;
        } else {
            $arguments['--filename-suffix'] = 'full';
            $arguments['--timeout'] = 600;
        }

        return $this->call('backup:run', $arguments);
    }
}
