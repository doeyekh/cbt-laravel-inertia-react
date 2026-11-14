<?php

namespace App\Models;

use Database\Factories\SessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'code', 'name', 'start_time', 'end_time', 'is_active'])]
class Session extends Model
{
    /** @use HasFactory<SessionFactory> */
    use HasFactory;

    protected $table = 'exam_sessions';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the school that owns this exam session.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the exam schedules that use this session.
     *
     * @return HasMany<ExamSchedule, $this>
     */
    public function examSchedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }
}
