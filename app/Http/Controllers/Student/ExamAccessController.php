<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Services\ExamAccessTokenService;
use App\Services\ExamScheduleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class ExamAccessController extends Controller
{
    public function __construct(
        private ExamScheduleAccessService $examAccess,
    ) {}

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

        if ($examPackage === null || ! $this->examAccess->participantMayAccess($participant, $examSchedule, $examPackage)) {
            abort(404);
        }

        $questionBank = $this->examAccess->resolveQuestionBank($examSchedule, $examPackage);
        $questionComposition = $questionBank?->composition ?? [];
        $totalQuestions = $questionBank?->getTotalQuestionCountAttribute() ?? 0;
        $totalScore = $questionBank?->getTotalScoreAttribute() ?? 0;

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
                    && $this->examAccess->isWithinPackageDateRange($examPackage, $examSchedule),
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

        if ($examPackage === null || ! $this->examAccess->participantMayAccess($participant, $examSchedule, $examPackage)) {
            abort(404);
        }

        if (
            $examPackage->status !== ExamPackageStatus::Aktif
            || $examSchedule->status !== ExamScheduleStatus::Aktif
        ) {
            return back()->withErrors([
                'token' => 'Paket ujian atau jadwal ujian tidak aktif.',
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

        return redirect()->to('/siswa/exams/'.$examScheduleId)->with('toast', [
            'type' => 'success',
            'title' => 'Token Terverifikasi',
            'description' => 'Token paket ujian valid untuk seluruh sesi dan ruangan dalam paket ini.',
        ]);
    }

    /**
     * Convert a stored time value (H:i:s) into H:i.
     */
    private function shortenTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
