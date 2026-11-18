<?php

namespace App\Models;

use App\Enums\ExamScheduleStatus;
use Database\Factories\ExamScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_package_id', 'class_id', 'subject_id', 'question_bank_id', 'session_id', 'room_id', 'exam_date', 'duration_minutes', 'open_time', 'close_time', 'shuffle_questions', 'shuffle_options', 'show_score_to_student', 'status'])]
class ExamSchedule extends Model
{
    /** @use HasFactory<ExamScheduleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_package_id' => 'integer',
            'class_id' => 'integer',
            'subject_id' => 'integer',
            'question_bank_id' => 'integer',
            'session_id' => 'integer',
            'room_id' => 'integer',
            'exam_date' => 'date',
            'duration_minutes' => 'integer',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_score_to_student' => 'boolean',
            'status' => ExamScheduleStatus::class,
        ];
    }

    /**
     * Get the exam package of this schedule.
     *
     * @return BelongsTo<ExamPackage, $this>
     */
    public function examPackage(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class);
    }

    /**
     * Get the class of this schedule.
     *
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the subject of this schedule.
     *
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the question bank assigned to this schedule.
     *
     * @return BelongsTo<QuestionBank, $this>
     */
    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    /**
     * Get the session of this schedule.
     *
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the room of this schedule.
     *
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
