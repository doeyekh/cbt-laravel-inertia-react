<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_active', 'school_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the questions created by this user.
     *
     * @return HasMany<Question, $this>
     */
    public function createdQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    /**
     * Get the school that the user belongs to.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the school where the user acts as headmaster.
     *
     * @return HasOne<School, $this>
     */
    public function headmasterOf(): HasOne
    {
        return $this->hasOne(School::class, 'headmaster_id');
    }

    /**
     * Get the school where the user acts as administrative staff.
     *
     * @return HasOne<School, $this>
     */
    public function adminOf(): HasOne
    {
        return $this->hasOne(School::class, 'admin_id');
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    /**
     * Check if user is a teacher (guru).
     */
    public function isGuru(): bool
    {
        return $this->role === UserRole::Guru;
    }

    /**
     * Check if the user is a guru without headmaster or school admin privileges.
     */
    public function isRestrictedGuru(): bool
    {
        return $this->isGuru() && ! $this->isSchoolAdmin() && ! $this->isHeadmaster();
    }

    /**
     * Get the subject teacher assignments of this user.
     *
     * @return HasMany<SubjectTeacher, $this>
     */
    public function subjectTeachers(): HasMany
    {
        return $this->hasMany(SubjectTeacher::class, 'teacher_id');
    }

    /**
     * Get the subject ids the user teaches in the given academic year.
     *
     * @return array<int, int>
     */
    public function taughtSubjectIds(?int $academicYearId = null): array
    {
        $academicYearId ??= (int) (AcademicYear::active()?->id ?? 0);

        if ($academicYearId <= 0) {
            return [];
        }

        return $this->subjectTeachers()
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->whereIn('school_class_id', SchoolClass::query()
                ->whereIn('school_id', $this->accessibleSchoolIds())
                ->where('academic_year_id', $academicYearId)
                ->select('id'))
            ->pluck('subject_id')
            ->map(fn ($subjectId): int => (int) $subjectId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get the class ids the user teaches, optionally limited to one subject.
     *
     * @return array<int, int>
     */
    public function taughtClassIds(?int $subjectId = null, ?int $academicYearId = null): array
    {
        $academicYearId ??= (int) (AcademicYear::active()?->id ?? 0);

        if ($academicYearId <= 0) {
            return [];
        }

        return $this->subjectTeachers()
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->when($subjectId !== null, fn ($query) => $query->where('subject_id', $subjectId))
            ->whereIn('school_class_id', SchoolClass::query()
                ->whereIn('school_id', $this->accessibleSchoolIds())
                ->where('academic_year_id', $academicYearId)
                ->select('id'))
            ->pluck('school_class_id')
            ->map(fn ($classId): int => (int) $classId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Check if user acts as a headmaster of any school.
     */
    public function isHeadmaster(): bool
    {
        return $this->headmasterOf()->exists();
    }

    /**
     * Get the identifiers of the schools the user can access.
     *
     * @return array<int, int>
     */
    public function accessibleSchoolIds(): array
    {
        if ($this->isAdministrator()) {
            return School::query()
                ->pluck('id')
                ->all();
        }

        $schoolIds = $this->headmasterOf()->pluck('id')
            ->merge($this->adminOf()->pluck('id'))
            ->push($this->school_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return array_map(
            fn ($schoolId): int => (int) $schoolId,
            $schoolIds,
        );
    }

    /**
     * Check if user acts as an admin of any school.
     */
    public function isSchoolAdmin(): bool
    {
        return $this->adminOf()->exists();
    }

    /**
     * Check if user acts as a headmaster of the specified school.
     */
    public function isHeadmasterOf(School $school): bool
    {
        return (int) $this->id === (int) $school->headmaster_id;
    }

    /**
     * Check if user acts as an admin of the specified school.
     */
    public function isSchoolAdminOf(School $school): bool
    {
        return (int) $this->id === (int) $school->admin_id;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
}
