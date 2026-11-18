<?php

namespace App\Models;

use Database\Factories\ExamAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_attempt_id', 'question_id', 'question_no', 'answer', 'is_flagged'])]
class ExamAnswer extends Model
{
    /** @use HasFactory<ExamAnswerFactory> */
    use HasFactory;

    private const ARRAY_ANSWER_PREFIX = '__array__:';

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
            'is_flagged' => 'boolean',
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

    /**
     * Serialize supported answer values for storage in the text column.
     *
     * @param  string|array<int, string>|null  $answer
     */
    public static function serializeAnswer(string|array|null $answer): ?string
    {
        if (! is_array($answer)) {
            return $answer;
        }

        return self::ARRAY_ANSWER_PREFIX.json_encode($answer, JSON_THROW_ON_ERROR);
    }

    /**
     * Restore an answer value from its stored representation.
     *
     * @return string|array<int, string>|null
     */
    public static function restoreAnswer(?string $answer): string|array|null
    {
        if (! is_string($answer) || ! str_starts_with($answer, self::ARRAY_ANSWER_PREFIX)) {
            return $answer;
        }

        $decodedAnswer = json_decode(substr($answer, strlen(self::ARRAY_ANSWER_PREFIX)), true);

        return is_array($decodedAnswer) ? $decodedAnswer : $answer;
    }
}
