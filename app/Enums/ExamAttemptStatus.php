<?php

namespace App\Enums;

enum ExamAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Graded = 'graded';

    /**
     * Get the Indonesian label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Sedang Dikerjakan',
            self::Submitted => 'Sudah Dikumpulkan',
            self::Graded => 'Sudah Dinilai',
        };
    }

    /**
     * Determine whether the participant may still change answers.
     */
    public function allowsAnswerChanges(): bool
    {
        return $this === self::InProgress;
    }
}
