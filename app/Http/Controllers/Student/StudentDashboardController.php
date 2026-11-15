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
            'exams' => $this->buildExamAgenda($participant, $classMember),
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
    private function buildExamAgenda(ExamParticipant $participant, ?ClassMember $classMember): array
    {
        if (! $classMember) {
            return [];
        }

        return ExamSchedule::query()
            ->with([
                'examPackage:id,name,exam_type_id',
                'examPackage.examType:id,name',
                'subject:id,name',
                'session:id,name',
                'room:id,name',
            ])
            ->where('class_id', $classMember->class_id)
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
                'subject' => $schedule->subject?->name,
                'package' => $schedule->examPackage?->name,
                'exam_type' => $schedule->examPackage?->examType?->name,
                'date' => $schedule->exam_date?->format('Y-m-d'),
                'open_time' => $this->shortenTime($schedule->open_time),
                'close_time' => $this->shortenTime($schedule->close_time),
                'duration_minutes' => $schedule->duration_minutes,
                'session' => $schedule->session?->name,
                'room' => $schedule->room?->name,
            ])
            ->all();
    }

    /**
     * Convert a stored time value (H:i:s) into H:i.
     */
    private function shortenTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
