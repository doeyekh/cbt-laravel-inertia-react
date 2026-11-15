<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    /**
     * Display the student portal dashboard.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $participant = ExamParticipant::query()
            ->with(['student', 'school', 'academicYear', 'session', 'room'])
            ->find($request->session()->get('student_participant_id'));

        if (! $participant) {
            $request->session()->forget(['student_participant_id', 'student_name']);

            return to_route('student.login')->with('toast', [
                'type' => 'info',
                'title' => 'Sesi Tidak Ditemukan',
                'description' => 'Silakan masuk kembali untuk membuka portal siswa.',
            ]);
        }

        $classMember = ClassMember::query()
            ->with('schoolClass:id,name')
            ->where('student_id', $participant->student_id)
            ->where('academic_year_id', $participant->academic_year_id)
            ->first();
        $verifiedExamPackages = $request->session()->get('student_exam_packages', []);

        if (! is_array($verifiedExamPackages)) {
            $verifiedExamPackages = [];
        }

        return Inertia::render('Student/Dashboard', [
            'title' => 'Portal Siswa — '.($participant->student?->name ?? 'Peserta Ujian'),
            'participant' => [
                'name' => $participant->student?->name ?? 'Peserta Ujian',
                'nisn' => $participant->student?->nisn,
                'username' => $participant->username,
                'participant_number' => $participant->participant_number,
                'school' => $participant->school?->name,
                'class_name' => $classMember?->schoolClass?->name,
                'academic_year' => $participant->academicYear?->name,
                'semester' => $participant->academicYear?->semester?->label(),
                'session' => $participant->session?->name,
                'room' => $participant->room?->name,
            ],
            'exams' => $this->buildExamAgenda(
                $participant,
                $classMember,
                $verifiedExamPackages,
            ),
            'systemInfo' => [
                'appName' => 'EduTest CBT',
                'systemName' => 'Sistem Evaluasi Terpadu',
                'version' => 'v4.6.2-Rel',
            ],
        ]);
    }

    /**
     * Build the exam agenda for the participant's class and academic year.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildExamAgenda(
        ExamParticipant $participant,
        ?ClassMember $classMember,
        array $verifiedExamPackages,
    ): array {
        if (! $classMember) {
            return [];
        }

        return ExamSchedule::query()
            ->with([
                'examPackage:id,name,code,exam_type_id,exam_access_token_hash',
                'examPackage.examType:id,name',
                'subject:id,name',
                'session:id,name',
                'room:id,name',
            ])
            ->where('class_id', $classMember->class_id)
            ->where('session_id', $participant->session_id)
            ->where('room_id', $participant->room_id)
            ->where('status', ExamScheduleStatus::Aktif)
            ->whereHas('examPackage', function (Builder $query) use ($participant): void {
                $query
                    ->where('school_id', $participant->school_id)
                    ->where('academic_year_id', $participant->academic_year_id)
                    ->where('status', ExamPackageStatus::Aktif);
            })
            ->orderBy('exam_date')
            ->orderBy('open_time')
            ->get()
            ->map(fn (ExamSchedule $schedule): array => [
                'id' => $schedule->id,
                'package_id' => $schedule->exam_package_id,
                'subject' => $schedule->subject?->name,
                'package' => $schedule->examPackage?->name,
                'package_code' => $schedule->examPackage?->code,
                'exam_type' => $schedule->examPackage?->examType?->name,
                'date' => $schedule->exam_date?->format('Y-m-d'),
                'open_time' => $this->shortenTime($schedule->open_time),
                'close_time' => $this->shortenTime($schedule->close_time),
                'duration_minutes' => $schedule->duration_minutes,
                'session' => $schedule->session?->name,
                'room' => $schedule->room?->name,
                'token_available' => $schedule->examPackage?->exam_access_token_hash !== null,
                'token_access_granted' => $this->hasExamPackageAccess(
                    $schedule,
                    $verifiedExamPackages,
                ),
            ])
            ->all();
    }

    /**
     * Determine whether the participant has verified the current package token.
     *
     * @param  array<int|string, mixed>  $verifiedExamPackages
     */
    private function hasExamPackageAccess(ExamSchedule $schedule, array $verifiedExamPackages): bool
    {
        $storedHash = $verifiedExamPackages[$schedule->exam_package_id] ?? null;
        $currentHash = $schedule->examPackage?->exam_access_token_hash;

        return is_string($storedHash)
            && is_string($currentHash)
            && hash_equals($currentHash, $storedHash);
    }

    /**
     * Convert a stored time value (H:i:s) into H:i.
     */
    private function shortenTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
