<?php

namespace App\Enums;

enum BackupType: string
{
    case Database = 'database';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'Database Saja',
            self::Full => 'Database + File',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Database => 'Hanya dump database. Cepat dan ukurannya lebih kecil.',
            self::Full => 'Dump database plus file aplikasi. Lebih lama dan ukurannya lebih besar.',
        };
    }

    public static function fromFilename(string $filename): self
    {
        return str_ends_with(strtolower($filename), '-full.zip')
            ? self::Full
            : self::Database;
    }
}
