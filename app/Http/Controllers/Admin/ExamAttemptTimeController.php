<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamAttemptTimeController extends Controller
{
    public function extend(Request $request, ExamAttempt $examAttempt): RedirectResponse
    {
        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:240'],
        ]);
        $this->authorizeAttemptSchool($request, $examAttempt);

        DB::transaction(function () use ($examAttempt, $validated): void {
            $attempt = ExamAttempt::query()
                ->lockForUpdate()
                ->findOrFail($examAttempt->id);

            abort_unless(
                $attempt->status === ExamAttemptStatus::InProgress,
                409,
                'Waktu hanya dapat ditambahkan pada attempt yang masih berlangsung.',
            );

            $this->extendDeadline($attempt, (int) $validated['minutes']);
        });

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Waktu Ujian Diperpanjang',
            'description' => "Waktu peserta ditambah {$validated['minutes']} menit.",
        ]);
    }

    public function unlock(Request $request, ExamAttempt $examAttempt): RedirectResponse
    {
        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:240'],
        ]);
        $this->authorizeAttemptSchool($request, $examAttempt);

        DB::transaction(function () use ($examAttempt, $validated): void {
            $attempt = ExamAttempt::query()
                ->lockForUpdate()
                ->findOrFail($examAttempt->id);

            abort_unless(
                $attempt->status !== ExamAttemptStatus::InProgress,
                409,
                'Attempt yang masih berlangsung tidak perlu dibuka kembali.',
            );

            $this->extendDeadline($attempt, (int) $validated['minutes']);
            $attempt->forceFill([
                'status' => ExamAttemptStatus::InProgress,
                'submitted_at' => null,
                'score' => null,
                'graded_at' => null,
                'graded_by' => null,
            ])->save();
            $attempt->answers()->update([
                'is_correct' => null,
                'score_awarded' => null,
            ]);
        });

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Attempt Dibuka Kembali',
            'description' => "Jawaban dipertahankan dan peserta mendapat tambahan waktu {$validated['minutes']} menit.",
        ]);
    }

    private function extendDeadline(ExamAttempt $attempt, int $minutes): void
    {
        $now = now();
        $deadline = $attempt->resolveDeadline();

        if ($deadline->lt($now)) {
            $deadline = $now;
        }

        $extendedDeadline = $deadline->addMinutes($minutes);
        $startedAt = $attempt->started_at ?? $attempt->created_at ?? $now;
        $requiredDurationMinutes = (int) ceil(
            max(0, $extendedDeadline->getTimestamp() - $startedAt->getTimestamp()) / 60,
        );

        $attempt->forceFill([
            'deadline_at' => $extendedDeadline,
            'duration_minutes' => max(
                (int) $attempt->duration_minutes + $minutes,
                $requiredDurationMinutes,
            ),
        ])->save();
    }

    private function authorizeAttemptSchool(Request $request, ExamAttempt $examAttempt): void
    {
        $examAttempt->loadMissing('examPackage');

        abort_unless(
            in_array(
                (int) $examAttempt->examPackage?->school_id,
                $request->user()->accessibleSchoolIds(),
                true,
            ),
            404,
        );
    }
}
