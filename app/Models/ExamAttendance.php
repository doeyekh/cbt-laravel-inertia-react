<?php

namespace App\Models;

use App\Enums\ExamAttendanceStatus;
use Database\Factories\ExamAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_schedule_id', 'exam_participant_id', 'status', 'check_in_time'])]
class ExamAttendance extends Model
{
    /** @use HasFactory<ExamAttendanceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_schedule_id' => 'integer',
            'exam_participant_id' => 'integer',
            'status' => ExamAttendanceStatus::class,
            'check_in_time' => 'datetime',
        ];
    }

    /**
     * Get the exam schedule of this attendance record.
     */
    public function examSchedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    /**
     * Get the exam participant of this attendance record.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExamParticipant::class, 'exam_participant_id');
    }

    /**
     * Determine whether the participant was present.
     */
    public function isPresent(): bool
    {
        return $this->status === ExamAttendanceStatus::Hadir;
    }
}
