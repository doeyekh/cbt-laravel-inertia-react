<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['question_bank_id', 'school_id', 'subject_id', 'exam_type_id', 'class_id', 'created_by', 'type', 'number', 'body', 'score', 'is_active', 'image_path', 'audio_path'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_bank_id' => 'integer',
            'school_id' => 'integer',
            'subject_id' => 'integer',
            'exam_type_id' => 'integer',
            'class_id' => 'integer',
            'created_by' => 'integer',
            'type' => QuestionType::class,
            'number' => 'integer',
            'score' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the question bank that stores this question.
     *
     * @return BelongsTo<QuestionBank, $this>
     */
    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    /**
     * Get the school that owns this question.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the subject of this question.
     *
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the exam type of this question.
     *
     * @return BelongsTo<ExamType, $this>
     */
    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    /**
     * Get the class of this question.
     *
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the creator of this question.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the answer options of this question.
     *
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    /**
     * Get the matching pairs of this question.
     *
     * @return HasMany<QuestionPair, $this>
     */
    public function pairs(): HasMany
    {
        return $this->hasMany(QuestionPair::class);
    }

    /**
     * Get the answer blanks of this question.
     *
     * @return HasMany<QuestionBlank, $this>
     */
    public function blanks(): HasMany
    {
        return $this->hasMany(QuestionBlank::class);
    }
}
