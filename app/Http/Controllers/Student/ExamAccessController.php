<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\QuestionBank;
use App\Services\ExamAccessTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class ExamAccessController extends Controller
{
    /**
     * Show the token entry page for the participant's scheduled exam.
     */
    public function create(Request $request, ExamSchedule $examSchedule): Response
    {
        $participant = ExamParticipant::query()
            ->with(['student', 'school', 'academicYear', 'session', 'room'])
            ->findOrFail((int) $request->session()->get('student_participant_id'));

        $examSchedule->loadMissing([
            'examPackage.examType',
            'schoolClass',
            'subject',
            'session',
            'room',
        ]);
        $examPackage = $examSchedule->examPackage;

        $questionBank = QuestionBank::query()
            ->where('school_id', $examPackage->school_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->where('exam_type_id', $examPackage->exam_type_id)
            ->where('subject_id', $examSchedule->subject_id)
            ->first();

        $questionComposition = $questionBank?->composition ?? [];
        $totalQuestions = $questionBank?->getTotalQuestionCountAttribute() ?? 0;
        $totalScore = $questionBank?->getTotalScoreAttribute() ?? 0;

        if ($examPackage === null || ! $this->participantMayAccessSchedule($participant, $examSchedule, $examPackage)) {
            abort(404);
        }

        $verifiedExamPackages = $request->session()->get('student_exam_packages', []);
        $storedHash = is_array($verifiedExamPackages)
            ? ($verifiedExamPackages[$examPackage->id] ?? null)
            : null;
        $currentHash = $examPackage->exam_access_token_hash;
        $tokenAccessGranted = is_string($storedHash)
            && is_string($currentHash)
            && hash_equals($currentHash, $storedHash);
        $tokenAvailable = $currentHash !== null;

        return Inertia::render('Student/ExamAccess', [
            'title' => 'Token Ujian — '.($examSchedule->subject?->name ?? 'Ujian'),
            'participant' => [
                'name' => $participant->student?->name ?? 'Peserta Ujian',
                'nisn' => $participant->student?->nisn,
                'username' => $participant->username,
                'participant_number' => $participant->participant_number,
                'school' => $participant->school?->name,
                'class_name' => $examSchedule->schoolClass?->name,
                'academic_year' => $participant->academicYear?->name,
                'semester' => $participant->academicYear?->semester?->label(),
                'session' => $participant->session?->name,
                'room' => $participant->room?->name,
            ],
            'systemInfo' => [
                'appName' => 'EduTest CBT',
                'systemName' => 'Sistem Evaluasi Terpadu',
                'version' => 'v4.6.2-Rel',
            ],
            'exam' => [
                'id' => $examSchedule->id,
                'subject' => $examSchedule->subject?->name,
                'package' => $examPackage->name,
                'package_code' => $examPackage->code,
                'exam_type' => $examPackage->examType?->name,
                'date' => $examSchedule->exam_date?->format('Y-m-d'),
                'open_time' => $this->shortenTime($examSchedule->open_time),
                'close_time' => $this->shortenTime($examSchedule->close_time),
                'duration_minutes' => $examSchedule->duration_minutes,
                'session' => $examSchedule->session?->name,
                'room' => $examSchedule->room?->name,
                'total_questions' => (int) $totalQuestions,
                'question_composition' => $questionComposition,
                'total_score' => (float) $totalScore,
                'token_available' => $tokenAvailable,
                'token_access_granted' => $tokenAccessGranted,
                'can_verify' => ! $tokenAccessGranted
                    && $tokenAvailable
                    && $examPackage->status === ExamPackageStatus::Aktif
                    && $examSchedule->status === ExamScheduleStatus::Aktif
                    && $this->isWithinPackageDateRange($examPackage, $examSchedule),
            ],
        ]);
    }

    /**
     * Verify the package token for the participant's scheduled exam.
     */
    public function verify(
        Request $request,
        $examScheduleId,
        ExamAccessTokenService $tokenService,
    ): RedirectResponse {
        $examSchedule = ExamSchedule::findOrFail($examScheduleId);
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

        return redirect()->to('/siswa/exams/' . $examScheduleId)->with('toast', [
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

    /**
     * Determine whether the exam schedule date falls within the package date range.
     */
    private function isWithinPackageDateRange(ExamPackage $examPackage, ExamSchedule $examSchedule): bool
    {
        $examDate = $examSchedule->exam_date?->toDateString();

        return $examDate !== null
            && $examDate >= $examPackage->start_date->toDateString()
            && $examDate <= $examPackage->end_date->toDateString();
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

    /**
     * Convert a stored time value (H:i:s) into H:i.
     */
    private function shortenTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
