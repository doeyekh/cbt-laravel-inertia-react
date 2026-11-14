<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'code',
    'address',
    'headmaster_id',
    'admin_id',
    'is_active',
    'username_template',
    'participant_number_template',
    'number_width',
])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'number_width' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the headmaster of this school.
     *
     * @return BelongsTo<User, $this>
     */
    public function headmaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'headmaster_id');
    }

    /**
     * Get the administrative user of this school.
     *
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the users that belong to this school.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the classes that belong to this school.
     *
     * @return HasMany<SchoolClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * Get the exam types that belong to this school.
     *
     * @return HasMany<ExamType, $this>
     */
    public function examTypes(): HasMany
    {
        return $this->hasMany(ExamType::class);
    }

    /**
     * Get the exam sessions that belong to this school.
     *
     * @return HasMany<Session, $this>
     */
    public function examSessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Get the exam rooms that belong to this school.
     *
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get the assessment types that belong to this school.
     *
     * @return HasMany<AssessmentType, $this>
     */
    public function assessmentTypes(): HasMany
    {
        return $this->hasMany(AssessmentType::class);
    }

    /**
     * Get the question banks that belong to this school.
     *
     * @return HasMany<QuestionBank, $this>
     */
    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    /**
     * Get the questions that belong to this school.
     *
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the exam packages that belong to this school.
     *
     * @return HasMany<ExamPackage, $this>
     */
    public function examPackages(): HasMany
    {
        return $this->hasMany(ExamPackage::class);
    }

    /**
     * Get the student registrations that belong to this school.
     *
     * @return HasMany<StudentRegistration, $this>
     */
    public function studentRegistrations(): HasMany
    {
        return $this->hasMany(StudentRegistration::class);
    }

    /**
     * Get the exam participants that belong to this school.
     *
     * @return HasMany<ExamParticipant, $this>
     */
    public function examParticipants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }

    /**
     * Get the administrator users that belong to this school.
     *
     * @return HasMany<User, $this>
     */
    public function administrators(): HasMany
    {
        return $this->users()->where('role', UserRole::Administrator->value);
    }

    /**
     * Get the teacher users that belong to this school.
     *
     * @return HasMany<User, $this>
     */
    public function gurus(): HasMany
    {
        return $this->users()->where('role', UserRole::Guru->value);
    }
}
