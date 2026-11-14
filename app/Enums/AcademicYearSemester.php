<?php

namespace App\Enums;

enum AcademicYearSemester: string
{
    case Ganjil = 'ganjil';
    case Genap = 'genap';

    public function codeSuffix(): string
    {
        return match ($this) {
            self::Ganjil => '1',
            self::Genap => '2',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Ganjil => 'Ganjil',
            self::Genap => 'Genap',
        };
    }
}
