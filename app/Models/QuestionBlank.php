<?php

namespace App\Models;

use Database\Factories\QuestionBlankFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['question_id', 'answers', 'score', 'order'])]
class QuestionBlank extends Model
{
    /** @use HasFactory<QuestionBlankFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_id' => 'integer',
            'answers' => 'array',
            'score' => 'float',
            'order' => 'integer',
        ];
    }

    /**
     * Get the question of this blank.
     *
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
