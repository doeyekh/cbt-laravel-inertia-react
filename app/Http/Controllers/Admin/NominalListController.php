<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesExamParticipantAccess;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Subject;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NominalListController extends Controller
{
    use AuthorizesExamParticipantAccess;

    /**
     * Display the nominal list (daftar peserta) per class.
     */
    public function index(Request $request): Response
    {
        $this->authorizeExamParticipantAccess($request->user());

        $data = $this->buildListData($request);

        return Inertia::render('Admin/NominalList/Index', [
            'title' => 'Daftar Nominatif Peserta',
            'participants' => $data['participants'],
            'subjects' => $data['subjects'],
            'classes' => $data['classes'],
            'schools' => $data['schools'],
            'sessions' => $data['sessions'],
            'rooms' => $data['rooms'],
            'activeAcademicYear' => $data['activeAcademicYear'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Export the nominal list to PDF.
     */
    public function pdf(Request $request): StreamedResponse
    {
        $this->authorizeExamParticipantAccess($request->user());

        $data = $this->buildListData($request, paginate: false);
        $participants = $data['participants'];

        $options = new Options;
        $options->set([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => [
                public_path(),
                storage_path('app/public'),
            ],
            'defaultFont' => 'DejaVu Sans',
            'defaultPaperSize' => 'A4',
            'dpi' => 96,
            'isPhpEnabled' => false,
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            view('nominal-list.pdf', [
                'participants' => $participants,
                'filters' => $data['filters'],
                'print_date' => now()->format('d/m/Y H:i'),
            ])->render(),
            'UTF-8',
        );
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Daftar-Nominatif-Peserta-'.now()->format('Ymd').'.pdf';

        return response()->stream(
            fn () => print ($dompdf->output()),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ],
        );
    }

    /**
     * Build the shared list data for the index page and PDF export.
     *
     * @return array<string, mixed>
     */
    private function buildListData(Request $request, bool $paginate = true): array
    {
        $schoolIds = $request->user()->accessibleSchoolIds();

        $request->validate([
            'school_id' => ['nullable', 'integer'],
            'academic_year_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
        ]);

        $schoolId = (int) $request->integer('school_id');
        $academicYearId = (int) $request->integer('academic_year_id');
        $classId = (int) $request->integer('class_id');
        $subjectId = (int) $request->integer('subject_id');
        $search = trim((string) $request->string('search'));

        if ($schoolId > 0 && ! in_array($schoolId, $schoolIds, true)) {
            $schoolId = 0;
        }

        if ($academicYearId <= 0) {
            $academicYearId = (int) (AcademicYear::active()?->id ?? 0);
        }

        $query = ClassMember::query()
            ->whereHas('schoolClass', fn ($query) => $query->whereIn('school_id', $schoolIds))
            ->when($academicYearId > 0, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($classId > 0, fn ($query) => $query->where('class_id', $classId))
            ->when($search !== '', fn ($query) => $query->whereHas('student', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            }))
            ->with([
                'student:id,name,nisn',
                'schoolClass:id,name',
            ])
            ->orderBy('student_id');

        $classMembers = $paginate
            ? $query->paginate(25)->withQueryString()
            : $query->get();

        $transform = function (ClassMember $classMember) use ($academicYearId): array {
            $student = $classMember->student;
            $participant = ExamParticipant::query()
                ->with(['session:id,name', 'room:id,name'])
                ->where('academic_year_id', $academicYearId)
                ->where('student_id', $student->id)
                ->first();

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->nisn,
                'class_name' => $classMember->schoolClass?->name,
                'participant_number' => $participant?->participant_number ?? '-',
                'username' => $participant?->username ?? '-',
                'session_name' => $participant?->session?->name ?? '-',
                'room_name' => $participant?->room?->name ?? '-',
                'has_credential' => $participant?->hasCredential() ?? false,
                'has_placement' => $participant?->hasPlacement() ?? false,
            ];
        };

        $participants = $paginate
            ? $classMembers->through($transform)
            : $classMembers->map($transform);

        $academicYearIds = ExamPackage::query()
            ->whereIn('school_id', $schoolIds)
            ->distinct()
            ->pluck('academic_year_id');
        $subjectIds = ExamSchedule::query()
            ->whereHas('examPackage', fn ($query) => $query->whereIn('school_id', $schoolIds))
            ->distinct()
            ->pluck('subject_id');

        return [
            'participants' => $participants,
            'subjects' => Subject::query()
                ->whereIn('id', $subjectIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'classes' => SchoolClass::query()
                ->whereIn('school_id', $schoolIds)
                ->when($academicYearId > 0, fn ($query) => $query->where('academic_year_id', $academicYearId))
                ->orderBy('name')
                ->get(['id', 'school_id', 'academic_year_id', 'code', 'name']),
            'schools' => School::query()
                ->whereIn('id', $schoolIds)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']),
            'sessions' => Session::query()
                ->whereIn('school_id', $schoolIds)
                ->orderBy('start_time')
                ->get(['id', 'school_id', 'code', 'name', 'start_time', 'end_time', 'is_active']),
            'rooms' => Room::query()
                ->whereIn('school_id', $schoolIds)
                ->orderBy('name')
                ->get(['id', 'school_id', 'code', 'name', 'capacity', 'is_active']),
            'activeAcademicYear' => AcademicYear::active()?->only(['id', 'code', 'name']),
            'filters' => [
                'school_id' => $schoolId > 0 ? $schoolId : null,
                'academic_year_id' => $academicYearId > 0 ? $academicYearId : null,
                'class_id' => $classId > 0 ? $classId : null,
                'subject_id' => $subjectId > 0 ? $subjectId : null,
                'search' => $search !== '' ? $search : null,
            ],
        ];
    }
}
