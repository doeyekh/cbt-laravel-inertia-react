<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nisn', 'name', 'gender', 'birth_place', 'birth_date', 'photo_path', 'father_name', 'father_occupation', 'father_phone', 'mother_name', 'mother_occupation', 'mother_phone', 'parent_address', 'parent_bio'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
        ];
    }

    /**
     * Get the school registrations of this student.
     *
     * @return HasMany<StudentRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(StudentRegistration::class);
    }

    /**
     * Get the currently active school registration of this student.
     *
     * @return HasOne<StudentRegistration, $this>
     */
    public function activeRegistration(): HasOne
    {
        return $this->hasOne(StudentRegistration::class)
            ->where('status', RegistrationStatus::Aktif->value);
    }

    /**
     * Get the class memberships of this student.
     *
     * @return HasMany<ClassMember, $this>
     */
    public function classMembers(): HasMany
    {
        return $this->hasMany(ClassMember::class);
    }

    /**
     * Get the exam participations of this student.
     *
     * @return HasMany<ExamParticipant, $this>
     */
    public function examParticipants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }
}
