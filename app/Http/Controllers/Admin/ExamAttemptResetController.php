<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamAttemptResetController extends Controller
{
    public function destroy(Request $request, ExamAttempt $examAttempt): RedirectResponse
    {
        $examAttempt->loadMissing('examPackage');

        abort_unless(
            in_array((int) $examAttempt->examPackage?->school_id, $request->user()->accessibleSchoolIds(), true),
            404,
        );

        DB::transaction(function () use ($examAttempt): void {
            $attempt = ExamAttempt::query()
                ->lockForUpdate()
                ->findOrFail($examAttempt->id);

            $attempt->answers()->delete();
            $attempt->delete();
        });

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Ujian Peserta Direset',
            'description' => 'Semua jawaban dan nilai dihapus. Peserta dapat memulai ulang ujian.',
        ]);
    }
}
