<?php

namespace App\Enums;

enum QuestionType: string
{
    case PilihanGanda = 'pilihan_ganda';
    case PilihanGandaKompleks = 'pilihan_ganda_kompleks';
    case Menjodohkan = 'menjodohkan';
    case IsianSingkat = 'isian_singkat';
    case Esai = 'esai';

    /**
     * Get the Indonesian label for the question type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PilihanGanda => 'Pilihan Ganda',
            self::PilihanGandaKompleks => 'Pilihan Ganda Kompleks',
            self::Menjodohkan => 'Menjodohkan',
            self::IsianSingkat => 'Isian',
            self::Esai => 'Esai',
        };
    }

    /**
     * Get the short label of the question type.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::PilihanGanda => 'PG',
            self::PilihanGandaKompleks => 'PK',
            self::Menjodohkan => 'MJ',
            self::IsianSingkat => 'IS',
            self::Esai => 'ES',
        };
    }

    /**
     * Get the column that stores the amount of this question type in a question bank.
     */
    public function countColumn(): string
    {
        return match ($this) {
            self::PilihanGanda => 'pilihan_ganda_count',
            self::PilihanGandaKompleks => 'pilihan_ganda_kompleks_count',
            self::Menjodohkan => 'menjodohkan_count',
            self::IsianSingkat => 'isian_count',
            self::Esai => 'esai_count',
        };
    }

    /**
     * Get the column that stores the score of this question type in a question bank.
     */
    public function scoreColumn(): string
    {
        return match ($this) {
            self::PilihanGanda => 'pilihan_ganda_score',
            self::PilihanGandaKompleks => 'pilihan_ganda_kompleks_score',
            self::Menjodohkan => 'menjodohkan_score',
            self::IsianSingkat => 'isian_score',
            self::Esai => 'esai_score',
        };
    }

    /**
     * Get the column that stores the amount of answer options of this question type.
     *
     * Types without answer options return null.
     */
    public function optionCountColumn(): ?string
    {
        return match ($this) {
            self::PilihanGanda => 'pilihan_ganda_option_count',
            self::PilihanGandaKompleks => 'pilihan_ganda_kompleks_option_count',
            default => null,
        };
    }

    /**
     * Determine whether the type uses answer options.
     */
    public function usesOptions(): bool
    {
        return in_array($this, [self::PilihanGanda, self::PilihanGandaKompleks], true);
    }

    /**
     * Determine whether the type uses matching pairs.
     */
    public function usesPairs(): bool
    {
        return $this === self::Menjodohkan;
    }

    /**
     * Determine whether the type uses answer blanks.
     */
    public function usesBlanks(): bool
    {
        return $this === self::IsianSingkat;
    }

    /**
     * Determine whether the type allows more than one correct answer.
     */
    public function allowsMultipleCorrectAnswers(): bool
    {
        return $this === self::PilihanGandaKompleks;
    }
}
