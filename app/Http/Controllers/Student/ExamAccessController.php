<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Services\ExamAccessTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;

class ExamAccessController extends Controller
{
    /**
     * Verify the package token for the participant's scheduled exam.
     */
    public function verify(
        Request $request,
        ExamSchedule $examSchedule,
        ExamAccessTokenService $tokenService,
    ): RedirectResponse {
        $participant = ExamParticipant::query()
            ->findOrFail((int) $request->session()->get('student_participant_id'));

        $examSchedule->loadMissing('examPackage');
        $examPackage = $examSchedule->examPackage;

        if ($examPackage === null || ! $this->participantMayAccessSchedule($participant, $examSchedule, $examPackage)) {
            abort(404);
        }

        if (
            $examPackage->status !== ExamPackageStatus::Aktif
            || $examSchedule->status !== ExamScheduleStatus::Aktif
            || ! $this->scheduleIsOpen($examPackage, $examSchedule)
        ) {
            return back()->withErrors([
                'token' => 'Jadwal ujian belum dibuka, sudah ditutup, atau tidak aktif.',
            ]);
        }

        if ($examPackage->exam_access_token_hash === null) {
            return back()->withErrors([
                'token' => 'Token untuk paket ujian ini belum diterbitkan.',
            ]);
        }

        $rateLimitKey = "exam-access-token:{$examPackage->id}:{$participant->id}";

        if (RateLimiter::increment($rateLimitKey, 300) > 5) {
            return back()->withErrors([
                'token' => 'Terlalu banyak percobaan. Silakan coba kembali dalam beberapa menit.',
            ]);
        }

        $submittedToken = $request->input('token');
        $token = is_string($submittedToken) ? strtoupper(trim($submittedToken)) : '';

        if (
            preg_match('/^[A-HJ-NP-Z2-9]{6}$/', $token) !== 1
            || ! $tokenService->matches($token, $examPackage->exam_access_token_hash)
        ) {
            return back()->withErrors([
                'token' => 'Token tidak sesuai.',
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        $verifiedExamPackages = $request->session()->get('student_exam_packages', []);
        if (! is_array($verifiedExamPackages)) {
            $verifiedExamPackages = [];
        }

        $verifiedExamPackages[$examPackage->id] = $examPackage->exam_access_token_hash;
        $request->session()->put('student_exam_packages', $verifiedExamPackages);

        return to_route('student.dashboard')->with('toast', [
            'type' => 'success',
            'title' => 'Token Terverifikasi',
            'description' => 'Token paket ujian valid untuk seluruh sesi dan ruangan dalam paket ini.',
        ]);
    }

    private function participantMayAccessSchedule(
        ExamParticipant $participant,
        ExamSchedule $examSchedule,
        ExamPackage $examPackage,
    ): bool {
        if (
            (int) $participant->school_id !== (int) $examPackage->school_id
            || (int) $participant->academic_year_id !== (int) $examPackage->academic_year_id
        ) {
            return false;
        }

        if (
            ($examSchedule->session_id !== null && (int) $participant->session_id !== (int) $examSchedule->session_id)
            || ($examSchedule->room_id !== null && (int) $participant->room_id !== (int) $examSchedule->room_id)
        ) {
            return false;
        }

        return ClassMember::query()
            ->where('student_id', $participant->student_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->where('class_id', $examSchedule->class_id)
            ->whereHas('schoolClass', fn ($query) => $query
                ->where('school_id', $examPackage->school_id)
                ->where('academic_year_id', $examPackage->academic_year_id))
            ->exists();
    }

    private function scheduleIsOpen(ExamPackage $examPackage, ExamSchedule $examSchedule): bool
    {
        if (
            $examSchedule->exam_date->lt($examPackage->start_date)
            || $examSchedule->exam_date->gt($examPackage->end_date)
        ) {
            return false;
        }

        $examDate = $examSchedule->exam_date->toDateString();
        $opensAt = Carbon::parse("{$examDate} {$examSchedule->open_time}");
        $closesAt = Carbon::parse("{$examDate} {$examSchedule->close_time}");
        $currentTime = now();

        return $currentTime->gte($opensAt) && $currentTime->lte($closesAt);
    }
}
