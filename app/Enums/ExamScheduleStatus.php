<?php

namespace App\Enums;

enum ExamScheduleStatus: string
{
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';

    /**
     * Get the Indonesian label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Nonaktif => 'Nonaktif',
        };
    }
}
