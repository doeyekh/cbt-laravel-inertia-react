<?php

namespace App\Models;

use App\Enums\ExamPackageStatus;
use Database\Factories\ExamPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'academic_year_id', 'exam_type_id', 'code', 'name', 'description', 'start_date', 'end_date', 'status'])]
class ExamPackage extends Model
{
    /** @use HasFactory<ExamPackageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'academic_year_id' => 'integer',
            'exam_type_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ExamPackageStatus::class,
        ];
    }

    /**
     * Get the school that owns this exam package.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the academic year of this exam package.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the exam type of this exam package.
     *
     * @return BelongsTo<ExamType, $this>
     */
    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    /**
     * Get the schedules that belong to this exam package.
     *
     * @return HasMany<ExamSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }
}
