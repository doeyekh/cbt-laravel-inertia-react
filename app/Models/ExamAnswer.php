<?php

namespace App\Models;

use Database\Factories\ExamAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_attempt_id', 'question_id', 'question_no', 'answer'])]
class ExamAnswer extends Model
{
    /** @use HasFactory<ExamAnswerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_attempt_id' => 'integer',
            'question_id' => 'integer',
            'question_no' => 'integer',
            'answer' => 'string',
        ];
    }

    /**
     * Get the attempt that owns this answer.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    /**
     * Get the question this answer belongs to.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
