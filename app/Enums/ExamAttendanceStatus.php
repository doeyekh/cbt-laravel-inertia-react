<?php

namespace App\Enums;

enum ExamAttendanceStatus: string
{
    case Hadir = 'hadir';
    case TidakHadir = 'tidak_hadir';
    case Ijin = 'ijin';
    case Sakit = 'sakit';

    /**
     * Get the Indonesian label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::TidakHadir => 'Tidak Hadir',
            self::Ijin => 'Ijin',
            self::Sakit => 'Sakit',
        };
    }

    /**
     * Determine whether the status counts as present.
     */
    public function isPresent(): bool
    {
        return $this === self::Hadir;
    }
}
