<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Database\Factories\StudentRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'school_id', 'nis', 'entry_academic_year_id', 'exit_academic_year_id', 'status', 'registered_at'])]
class StudentRegistration extends Model
{
    /** @use HasFactory<StudentRegistrationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'school_id' => 'integer',
            'entry_academic_year_id' => 'integer',
            'exit_academic_year_id' => 'integer',
            'status' => RegistrationStatus::class,
            'registered_at' => 'date',
        ];
    }

    /**
     * Get the student of this registration.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the school of this registration.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the academic year when the student entered the school.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function entryAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'entry_academic_year_id');
    }

    /**
     * Get the academic year when the student left the school.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function exitAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'exit_academic_year_id');
    }
}
