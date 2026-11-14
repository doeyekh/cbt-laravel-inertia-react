<?php

namespace App\Models;

use Database\Factories\SchoolClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'academic_year_id', 'code', 'name', 'is_active'])]
class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classes';

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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the school that owns this class.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the academic year of this class.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the class memberships of this class.
     *
     * @return HasMany<ClassMember, $this>
     */
    public function classMembers(): HasMany
    {
        return $this->hasMany(ClassMember::class, 'class_id');
    }

    /**
     * Get the students that belong to this class.
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_members', 'class_id', 'student_id')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }

    /**
     * Get the questions of this class.
     *
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'class_id');
    }

    /**
     * Get the exam schedules of this class.
     *
     * @return HasMany<ExamSchedule, $this>
     */
    public function examSchedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class, 'class_id');
    }
}
