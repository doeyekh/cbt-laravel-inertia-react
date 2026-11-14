<?php

namespace App\Models;

use App\Enums\ExamPackageStatus;
use Database\Factories\ExamTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'code', 'name', 'description', 'is_active'])]
class ExamType extends Model
{
    /** @use HasFactory<ExamTypeFactory> */
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
        ];
    }

    /**
     * Get the school that owns this exam type.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the exam packages that use this exam type.
     *
     * @return HasMany<ExamPackage, $this>
     */
    public function examPackages(): HasMany
    {
        return $this->hasMany(ExamPackage::class);
    }

    /**
     * Keep the related exam packages in sync with the exam type status.
     */
    protected static function booted(): void
    {
        static::updated(function (self $examType): void {
            if (! $examType->wasChanged('is_active')) {
                return;
            }

            $examType->examPackages()
                ->where('status', '!=', ExamPackageStatus::Draft->value)
                ->update([
                    'status' => ExamPackageStatus::Draft->value,
                ]);
        });
    }
}
