<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionBankFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'subject_id', 'academic_year_id', 'exam_type_id', 'code', 'name', 'description', 'pilihan_ganda_count', 'pilihan_ganda_kompleks_count', 'menjodohkan_count', 'isian_count', 'esai_count', 'pilihan_ganda_score', 'pilihan_ganda_kompleks_score', 'menjodohkan_score', 'isian_score', 'esai_score', 'pilihan_ganda_option_count', 'pilihan_ganda_kompleks_option_count', 'is_active'])]
class QuestionBank extends Model
{
    /** @use HasFactory<QuestionBankFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'subject_id' => 'integer',
            'academic_year_id' => 'integer',
            'exam_type_id' => 'integer',
            'pilihan_ganda_count' => 'integer',
            'pilihan_ganda_kompleks_count' => 'integer',
            'menjodohkan_count' => 'integer',
            'isian_count' => 'integer',
            'esai_count' => 'integer',
            'pilihan_ganda_score' => 'float',
            'pilihan_ganda_kompleks_score' => 'float',
            'menjodohkan_score' => 'float',
            'isian_score' => 'float',
            'esai_score' => 'float',
            'pilihan_ganda_option_count' => 'integer',
            'pilihan_ganda_kompleks_option_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the school that owns this question bank.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the subject of this question bank.
     *
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the academic year this question bank belongs to.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the exam type of this question bank.
     *
     * @return BelongsTo<ExamType, $this>
     */
    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    /**
     * Get the classes this question bank applies to.
     *
     * @return BelongsToMany<SchoolClass, $this>
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'question_bank_class',
            'question_bank_id',
            'class_id'
        )->withTimestamps();
    }

    /**
     * Get the questions stored in this question bank.
     *
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the total amount of questions designed for this question bank.
     */
    public function getTotalQuestionCountAttribute(): int
    {
        return (int) $this->pilihan_ganda_count
            + (int) $this->pilihan_ganda_kompleks_count
            + (int) $this->menjodohkan_count
            + (int) $this->isian_count
            + (int) $this->esai_count;
    }

    /**
     * Get the amount of questions per type keyed by the question type value.
     *
     * @return array<string, int>
     */
    public function getCompositionAttribute(): array
    {
        $composition = [];

        foreach (QuestionType::cases() as $type) {
            $composition[$type->value] = (int) $this->{$type->countColumn()};
        }

        return $composition;
    }

    /**
     * Get the score of every question type keyed by the question type value.
     *
     * @return array<string, float>
     */
    public function getScoresAttribute(): array
    {
        $scores = [];

        foreach (QuestionType::cases() as $type) {
            $scores[$type->value] = (float) $this->{$type->scoreColumn()};
        }

        return $scores;
    }

    /**
     * Get the amount of answer options per type keyed by the question type value.
     *
     * Types without answer options are reported with a count of zero.
     *
     * @return array<string, int>
     */
    public function getOptionCountsAttribute(): array
    {
        $optionCounts = [];

        foreach (QuestionType::cases() as $type) {
            $column = $type->optionCountColumn();

            $optionCounts[$type->value] = $column === null ? 0 : (int) $this->{$column};
        }

        return $optionCounts;
    }

    /**
     * Get the total score of the question bank based on the composition and the score of each type.
     */
    public function getTotalScoreAttribute(): float
    {
        $total = 0.0;

        foreach (QuestionType::cases() as $type) {
            $total += (int) $this->{$type->countColumn()} * (float) $this->{$type->scoreColumn()};
        }

        return round($total, 2);
    }
}
