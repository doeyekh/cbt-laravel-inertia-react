<?php

namespace App\Enums;

enum ExamPackageStatus: string
{
    case Draft = 'draft';
    case Aktif = 'aktif';
    case Selesai = 'selesai';

    /**
     * Get the Indonesian label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Aktif => 'Aktif',
            self::Selesai => 'Selesai',
        };
    }
}
