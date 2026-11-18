<?php

namespace App\Models;

use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exam_participant_id', 'exam_schedule_id', 'exam_package_id', 'duration_minutes', 'time_spent_seconds', 'started_at', 'submitted_at'])]
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_participant_id' => 'integer',
            'exam_schedule_id' => 'integer',
            'exam_package_id' => 'integer',
            'duration_minutes' => 'integer',
            'time_spent_seconds' => 'integer',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Get the participant who owns this attempt.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExamParticipant::class, 'exam_participant_id');
    }

    /**
     * Get the exam schedule of this attempt.
     */
    public function examSchedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    /**
     * Get the exam package of this attempt.
     */
    public function examPackage(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id');
    }

    /**
     * Get the answers of this attempt.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'exam_attempt_id');
    }
}
