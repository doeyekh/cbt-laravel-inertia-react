<?php

namespace App\Models;

use App\Enums\AcademicYearSemester;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

#[Fillable(['name', 'semester', 'is_active'])]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => AcademicYearSemester::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $academicYear): void {
            $academicYear->code = $academicYear->generateCode();
        });

        static::saved(function (self $academicYear): void {
            if (! $academicYear->is_active) {
                return;
            }

            self::query()
                ->whereKeyNot($academicYear->getKey())
                ->where('is_active', true)
                ->update(['is_active' => false]);
        });
    }

    /**
     * Build the code from the school year and semester, e.g. 2024/2025 ganjil => 20241.
     */
    public function generateCode(): string
    {
        if (! preg_match('/^(\d{4})\/\d{4}$/', (string) $this->name, $matches)) {
            throw new InvalidArgumentException('Nama tahun pelajaran harus berformat YYYY/YYYY.');
        }

        if (! $this->semester instanceof AcademicYearSemester) {
            throw new InvalidArgumentException('Semester tahun pelajaran wajib diisi.');
        }

        return $matches[1].$this->semester->codeSuffix();
    }

    /**
     * Get the academic year that is currently active.
     */
    public static function active(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->orderByDesc('code')
            ->first();
    }

    /**
     * Get the classes that belong to this academic year.
     *
     * @return HasMany<SchoolClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * Get the exam packages that belong to this academic year.
     *
     * @return HasMany<ExamPackage, $this>
     */
    public function examPackages(): HasMany
    {
        return $this->hasMany(ExamPackage::class);
    }
}
