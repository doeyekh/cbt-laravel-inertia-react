<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'code', 'name', 'building', 'floor', 'capacity', 'is_active'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
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
            'is_active' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    /**
     * Get the school that owns this exam room.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the exam schedules that use this room.
     *
     * @return HasMany<ExamSchedule, $this>
     */
    public function examSchedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }
}
