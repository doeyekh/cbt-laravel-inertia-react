<?php

namespace App\Models;

use Database\Factories\ExamParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'school_id',
    'academic_year_id',
    'session_id',
    'room_id',
    'username',
    'participant_number',
    'password',
    'password_plain',
])]
#[Hidden(['password', 'password_plain'])]
class ExamParticipant extends Model
{
    /** @use HasFactory<ExamParticipantFactory> */
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
            'academic_year_id' => 'integer',
            'session_id' => 'integer',
            'room_id' => 'integer',
            'password' => 'hashed',
            'password_plain' => 'encrypted',
        ];
    }

    /**
     * Get the student of this participant.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the school of this participant.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the academic year of this participant.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the session assigned to this participant.
     *
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the room assigned to this participant.
     *
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Determine whether the participant already has a complete credential.
     */
    public function hasCredential(): bool
    {
        return filled($this->username) && filled($this->participant_number);
    }

    /**
     * Determine whether the participant already has a room and session.
     */
    public function hasPlacement(): bool
    {
        return filled($this->room_id) && filled($this->session_id);
    }
}
