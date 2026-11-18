<?php

namespace App\Models;

use App\Enums\ExamAttemptStatus;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['exam_participant_id', 'exam_schedule_id', 'exam_package_id', 'status', 'duration_minutes', 'time_spent_seconds', 'started_at', 'deadline_at', 'submitted_at', 'score', 'max_score', 'graded_at', 'graded_by', 'question_order', 'option_order'])]
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_participant_id' => 'integer',
            'exam_schedule_id' => 'integer',
            'exam_package_id' => 'integer',
            'status' => ExamAttemptStatus::class,
            'duration_minutes' => 'integer',
            'time_spent_seconds' => 'integer',
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'graded_at' => 'datetime',
            'graded_by' => 'integer',
            'question_order' => 'array',
            'option_order' => 'array',
        ];
    }

    /**
     * Resolve the deadline used to enforce the exam duration.
     *
     * Batas waktu tidak pernah melewati `started_at + duration_minutes`,
     * sekalipun `deadline_at` tersimpan lebih longgar. Dengan begitu angka
     * hitung mundur dan penolakan penyimpanan di server selalu memakai
     * tenggat yang sama.
     *
     * Attempt yang dibuat sebelum kolom `deadline_at` ada tidak memiliki
     * batas waktu tersimpan. Untuk baris lama, batas waktu dihitung dari
     * waktu attempt pertama kali dibuat agar server tetap menjadi sumber
     * kebenaran durasi ujian.
     */
    public function resolveDeadline(): Carbon
    {
        if ($this->deadline_at === null) {
            $fallbackStart = $this->created_at ?? $this->started_at ?? now();

            return $fallbackStart->copy()->addMinutes(max(0, (int) $this->duration_minutes));
        }

        $startedAt = $this->started_at ?? $this->created_at;

        if ($startedAt !== null) {
            $durationDeadline = $startedAt->copy()->addMinutes(max(0, (int) $this->duration_minutes));

            if ($this->deadline_at->gt($durationDeadline)) {
                return $durationDeadline;
            }
        }

        return $this->deadline_at->copy();
    }

    /**
     * Get the total working time of this attempt in seconds.
     *
     * Durasi disimpan pada attempt saat pertama kali dibuat, sehingga
     * perubahan jadwal setelah ujian dimulai tidak mengubah waktu peserta.
     */
    public function durationSeconds(): int
    {
        return max(0, (int) $this->duration_minutes * 60);
    }

    /**
     * Get the remaining working time in seconds.
     */
    public function remainingSeconds(): int
    {
        return (int) max(0, $this->resolveDeadline()->getTimestamp() - now()->getTimestamp());
    }

    /**
     * Determine whether the participant has no working time left.
     */
    public function hasReachedDeadline(): bool
    {
        return $this->remainingSeconds() === 0;
    }

    /**
     * Get the time already spent working, capped at the configured duration.
     */
    public function elapsedSeconds(): int
    {
        $durationSeconds = $this->durationSeconds();

        return (int) max(0, min($durationSeconds, $durationSeconds - $this->remainingSeconds()));
    }

    /**
     * Determine whether the attempt has already been submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status !== null && ! $this->status->allowsAnswerChanges();
    }

    /**
     * Finalize the attempt so its answers can no longer be changed.
     */
    public function submit(): bool
    {
        if ($this->isSubmitted()) {
            return false;
        }

        $submittedAt = now();

        $this->forceFill([
            'status' => ExamAttemptStatus::Submitted,
            'submitted_at' => $submittedAt,
            'time_spent_seconds' => $this->elapsedSeconds(),
        ])->save();

        return true;
    }

    /**
     * Get the participant who owns this attempt.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExamParticipant::class, 'exam_participant_id');
    }

    /**
     * Get the exam schedule of this attempt.
     */
    public function examSchedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    /**
     * Get the exam package of this attempt.
     */
    public function examPackage(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id');
    }

    /**
     * Get the answers of this attempt.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'exam_attempt_id');
    }
}
