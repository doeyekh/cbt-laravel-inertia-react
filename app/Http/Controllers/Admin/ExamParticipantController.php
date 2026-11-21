<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Admin\Concerns\AuthorizesExamParticipantAccess;
use App\Http\Controllers\Admin\Concerns\PresentsExamParticipant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateCredentialsRequest;
use App\Http\Requests\Admin\SyncParticipantsRequest;
use App\Http\Requests\Admin\UpdateExamParticipantRequest;
use App\Http\Requests\Admin\UpdateSchoolCredentialTemplateRequest;
use App\Models\AcademicYear;
use App\Models\ExamParticipant;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Services\ExamParticipantCredentialService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamParticipantController extends Controller
{
    use AuthorizesExamParticipantAccess;
    use PresentsExamParticipant;

    public function __construct(private readonly ExamParticipantCredentialService $credentialService) {}

    /**
     * Display the flat listing of exam participants.
     */
    public function index(Request $request): Response
    {
        $this->authorizeExamParticipantAccess($request->user());

        $accessibleSchoolIds = $request->user()->accessibleSchoolIds();

        $search = trim((string) $request->string('search'));
        $schoolFilter = (int) $request->integer('school_id');
        $academicYearFilter = (int) $request->integer('academic_year_id');
        $classFilter = (int) $request->integer('class_id');
        $sessionFilter = (int) $request->integer('session_id');
        $roomFilter = (int) $request->integer('room_id');
        $statusFilter = $request->string('status')->toString();

        if (! in_array($schoolFilter, $accessibleSchoolIds, true)) {
            $schoolFilter = 0;
        }

        if (! in_array($statusFilter, ['lengkap', 'belum'], true)) {
            $statusFilter = '';
        }

        if ($academicYearFilter <= 0) {
            $academicYearFilter = (int) (AcademicYear::active()?->id ?? 0);
        }

        $baseQuery = ExamParticipant::query()
            ->whereIn('school_id', $accessibleSchoolIds)
            ->when($schoolFilter > 0, fn ($query) => $query->where('school_id', $schoolFilter))
            ->when($academicYearFilter > 0, fn ($query) => $query->where('academic_year_id', $academicYearFilter))
            ->when($classFilter > 0, fn ($query) => $query->whereHas(
                'student.classMembers',
                fn ($query) => $query->where('class_id', $classFilter),
            ))
            ->when($sessionFilter > 0, fn ($query) => $query->where('session_id', $sessionFilter))
            ->when($roomFilter > 0, fn ($query) => $query->where('room_id', $roomFilter))
            ->when($statusFilter === 'lengkap', function ($query): void {
                $query->whereNotNull('username')->whereNotNull('participant_number');
            })
            ->when($statusFilter === 'belum', function ($query): void {
                $query->where(function ($query): void {
                    $query->whereNull('username')->orWhereNull('participant_number');
                });
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('participant_number', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('nisn', 'like', "%{$search}%")
                                ->orWhereHas('registrations', function ($query) use ($search): void {
                                    $query->where('nis', 'like', "%{$search}%");
                                });
                        });
                });
            });

        $participants = (clone $baseQuery)
            ->with([
                'student:id,name,nisn',
                'student.registrations' => fn ($query) => $query->whereIn('school_id', $accessibleSchoolIds),
                'student.classMembers' => fn ($query) => $query->with('schoolClass:id,code,name'),
                'academicYear:id,code,name',
                'session:id,code,name,start_time,end_time',
                'room:id,code,name',
            ])
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ExamParticipant $participant): array => $this->presentExamParticipant($participant));

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'with_credential' => (clone $baseQuery)
                ->whereNotNull('username')
                ->whereNotNull('participant_number')
                ->count(),
            'without_placement' => (clone $baseQuery)
                ->where(function ($query): void {
                    $query->whereNull('room_id')->orWhereNull('session_id');
                })
                ->count(),
        ];

        return Inertia::render('ExamParticipants/Index', [
            'title' => 'Peserta Ujian',
            'participants' => $participants,
            'schools' => School::query()
                ->whereIn('id', $accessibleSchoolIds)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'code',
                    'username_template',
                    'participant_number_template',
                    'number_width',
                ]),
            'classes' => SchoolClass::query()
                ->whereIn('school_id', $accessibleSchoolIds)
                ->orderBy('name')
                ->get(['id', 'school_id', 'academic_year_id', 'code', 'name']),
            'sessions' => Session::query()
                ->whereIn('school_id', $accessibleSchoolIds)
                ->orderBy('start_time')
                ->get(['id', 'school_id', 'code', 'name', 'start_time', 'end_time', 'is_active']),
            'rooms' => Room::query()
                ->whereIn('school_id', $accessibleSchoolIds)
                ->orderBy('name')
                ->get(['id', 'school_id', 'code', 'name', 'capacity', 'is_active']),
            'activeAcademicYear' => AcademicYear::active()?->only(['id', 'code', 'name']),
            'filters' => [
                'search' => $search,
                'school_id' => $schoolFilter > 0 ? $schoolFilter : null,
                'academic_year_id' => $academicYearFilter > 0 ? $academicYearFilter : null,
                'class_id' => $classFilter > 0 ? $classFilter : null,
                'session_id' => $sessionFilter > 0 ? $sessionFilter : null,
                'room_id' => $roomFilter > 0 ? $roomFilter : null,
                'status' => $statusFilter !== '' ? $statusFilter : null,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Update the given participant credential and placement.
     */
    public function update(
        UpdateExamParticipantRequest $request,
        ExamParticipant $examParticipant,
    ): RedirectResponse {
        $this->authorizeExamParticipantManage($request->user());
        $this->authorizeParticipantAccess($request->user(), $examParticipant);

        $data = $request->validated();

        $examParticipant->update([
            'session_id' => array_key_exists('session_id', $data)
                ? $this->nullableId($data['session_id'])
                : $examParticipant->session_id,
            'room_id' => array_key_exists('room_id', $data)
                ? $this->nullableId($data['room_id'])
                : $examParticipant->room_id,
            'username' => array_key_exists('username', $data)
                ? $this->nullableValue($data['username'])
                : $examParticipant->username,
            'participant_number' => array_key_exists('participant_number', $data)
                ? $this->nullableValue($data['participant_number'])
                : $examParticipant->participant_number,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil Diperbarui',
            'description' => "Data peserta {$examParticipant->student?->name} telah diperbarui.",
        ]);
    }

    /**
     * Remove the given participant from the exam participant list.
     */
    public function destroy(Request $request, ExamParticipant $examParticipant): RedirectResponse
    {
        $this->authorizeExamParticipantManage($request->user());
        $this->authorizeParticipantAccess($request->user(), $examParticipant);

        $name = $examParticipant->student?->name;

        $examParticipant->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Peserta Dikeluarkan',
            'description' => "{$name} telah dikeluarkan dari daftar peserta ujian.",
        ]);
    }

    /**
     * Pull the members of the selected classes into the participant list.
     */
    public function sync(SyncParticipantsRequest $request): RedirectResponse
    {
        $this->authorizeExamParticipantManage($request->user());

        $data = $request->validated();
        $schoolId = (int) $data['school_id'];
        $academicYearId = (int) $data['academic_year_id'];

        $this->authorizeParticipantSchoolAccess($request->user(), $schoolId);

        $school = School::query()->findOrFail($schoolId);
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);

        $created = DB::transaction(fn (): int => $this->credentialService->syncParticipantsFromClasses(
            $school,
            $academicYear,
            array_map('intval', $data['class_ids']),
            $this->credentialService->currentPlacement($schoolId, $academicYearId),
        ));

        return back()->with('toast', [
            'type' => $created > 0 ? 'success' : 'info',
            'title' => $created > 0 ? 'Peserta Ditambahkan' : 'Tidak Ada Peserta Baru',
            'description' => $created > 0
                ? "{$created} peserta ditambahkan dari anggota kelas terpilih."
                : 'Seluruh anggota kelas terpilih sudah terdaftar sebagai peserta.',
        ]);
    }

    /**
     * Generate credentials for every participant of the school and academic year.
     */
    public function generate(GenerateCredentialsRequest $request): RedirectResponse
    {
        $this->authorizeExamParticipantManage($request->user());

        $data = $request->validated();
        $schoolId = (int) $data['school_id'];
        $academicYearId = (int) $data['academic_year_id'];

        $this->authorizeParticipantSchoolAccess($request->user(), $schoolId);

        $school = School::query()->findOrFail($schoolId);
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);

        $participants = $this->credentialService->participantsWithRelations($school, $academicYear);

        if ($participants->isEmpty()) {
            return back()->with('toast', [
                'type' => 'warning',
                'title' => 'Tidak Ada Peserta',
                'description' => 'Belum ada peserta pada sekolah dan tahun pelajaran tersebut. Tarik dari kelas terlebih dahulu.',
            ]);
        }

        $updated = $this->credentialService->generateCredentials(
            $school,
            $academicYear,
            $participants,
            (bool) ($data['overwrite'] ?? false),
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Kredensial Dibangkitkan',
            'description' => "Kredensial {$updated} peserta berhasil dibuat pada sekolah {$school->name}.",
        ]);
    }

    /**
     * Update the room and session of the selected participants in bulk.
     */
    public function bulkPlacement(Request $request): RedirectResponse
    {
        $this->authorizeExamParticipantManage($request->user());

        $data = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
        ], [
            'school_id.required' => 'Sekolah wajib dipilih.',
            'school_id.exists' => 'Sekolah tidak valid.',
            'participant_ids.required' => 'Pilih minimal satu peserta.',
            'participant_ids.min' => 'Pilih minimal satu peserta.',
            'room_id.required' => 'Ruangan wajib dipilih.',
            'room_id.exists' => 'Ruangan tidak valid.',
            'session_id.required' => 'Sesi wajib dipilih.',
            'session_id.exists' => 'Sesi tidak valid.',
        ]);

        $schoolId = (int) $data['school_id'];

        $this->authorizeParticipantSchoolAccess($request->user(), $schoolId);

        $room = Room::query()->find((int) $data['room_id']);
        $session = Session::query()->find((int) $data['session_id']);

        if ($room === null || (int) $room->school_id !== $schoolId) {
            return back()->withErrors(['room_id' => 'Ruangan harus berasal dari sekolah peserta.']);
        }

        if ($session === null || (int) $session->school_id !== $schoolId) {
            return back()->withErrors(['session_id' => 'Sesi harus berasal dari sekolah peserta.']);
        }

        $participantIds = array_values(array_unique(array_map('intval', $data['participant_ids'])));
        $academicYearId = (int) ($data['academic_year_id'] ?? 0);

        $scopedIds = ExamParticipant::query()
            ->where('school_id', $schoolId)
            ->when($academicYearId > 0, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->whereIn('id', $participantIds)
            ->pluck('id');

        $moved = $this->credentialService->moveParticipants($scopedIds, (int) $room->id, (int) $session->id);

        if ($moved === 0) {
            return back()->withErrors([
                'participant_ids' => 'Peserta yang dipilih tidak ditemukan pada sekolah ini.',
            ]);
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Penempatan Diperbarui',
            'description' => "{$moved} peserta dipindahkan ke {$room->name} pada {$session->name}.",
        ]);
    }

    /**
     * Generate a new password for the given participant.
     */
    public function resetPassword(Request $request, ExamParticipant $examParticipant): RedirectResponse
    {
        $this->authorizeExamParticipantManage($request->user());
        $this->authorizeParticipantAccess($request->user(), $examParticipant);

        $password = $this->credentialService->generatePassword();

        $examParticipant->update([
            'password' => $password,
            'password_plain' => $password,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Password Diacak Ulang',
            'description' => "Password baru untuk {$examParticipant->student?->name} sudah dibuat.",
        ]);
    }

    /**
     * Update the credential template of the given school.
     */
    public function updateCredentialTemplate(
        UpdateSchoolCredentialTemplateRequest $request,
        School $school,
    ): RedirectResponse {
        $this->authorizeExamParticipantManage($request->user());
        $this->authorizeParticipantSchoolAccess($request->user(), (int) $school->id);

        $data = $request->validated();

        $school->update([
            'username_template' => trim((string) $data['username_template']),
            'participant_number_template' => trim((string) $data['participant_number_template']),
            'number_width' => (int) $data['number_width'],
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Template Kredensial Diperbarui',
            'description' => "Template peserta {$school->name} berhasil disimpan.",
        ]);
    }

    /**
     * Print the participant cards of a school and academic year.
     */
    public function cards(Request $request): StreamedResponse
    {
        $this->authorizeExamParticipantAccess($request->user());

        $schoolId = (int) $request->integer('school_id');
        $academicYearId = (int) $request->integer('academic_year_id');
        $sessionId = (int) $request->integer('session_id');
        $roomId = (int) $request->integer('room_id');
        $classId = (int) $request->integer('class_id');

        abort_if($schoolId <= 0 || $academicYearId <= 0, 403);

        $this->authorizeParticipantSchoolAccess($request->user(), $schoolId);
        $school = School::query()
            ->with('headmaster:id,name')
            ->findOrFail($schoolId);
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);

        $participants = ExamParticipant::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->when($sessionId > 0, fn ($query) => $query->where('session_id', $sessionId))
            ->when($roomId > 0, fn ($query) => $query->where('room_id', $roomId))
            ->when($classId > 0, fn ($query) => $query->whereHas(
                'student.classMembers',
                fn ($query) => $query->where('class_id', $classId)
                    ->where('academic_year_id', $academicYearId),
            ))
            ->with([
                'student.registrations' => fn ($query) => $query->where('school_id', $schoolId),
                'student.classMembers' => fn ($query) => $query->with('schoolClass:id,code,name'),
                'session:id,code,name,start_time,end_time',
                'room:id,code,name',
            ])
            ->orderBy('participant_number')
            ->get()
            ->map(function (ExamParticipant $participant): array {
                $registrations = $participant->student?->registrations ?? collect();

                $registration = $registrations->firstWhere('status', RegistrationStatus::Aktif)
                    ?? $registrations->first();

                $classMember = $participant->student?->classMembers
                    ?->firstWhere('academic_year_id', $participant->academic_year_id);

                return [
                    'name' => $participant->student?->name ?? '-',
                    'nis' => $registration?->nis ?? '-',
                    'class_name' => $classMember?->schoolClass?->name ?? '-',
                    'participant_number' => $participant->participant_number ?? '-',
                    'username' => $participant->username ?? '-',
                    'password' => $participant->password_plain ?? '-',
                    'session_name' => $participant->session?->name ?? '-',
                    'session_time' => $this->sessionTime($participant),
                    'room_name' => $participant->room?->name ?? '-',
                ];
            })
            ->values()
            ->all();

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

       
        $html = view('exam-participant.cards', [
            'school' => [
                'name' => $school->name,
                'code' => $school->code,
                'headmaster_name' => $school->headmaster?->name ?? '-',
            ],
            'academic_year' => [
                'code' => $academicYear->code,
                'name' => $academicYear->name,
            ],
            'participants' => $participants,
            'print_date' => now()->format('d/m/Y H:i'),
        ])->render();

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Kartu-Peserta-'.$school->code.'-'.$academicYear->code.'-'.now()->format('Ymd').'.pdf';

        return response()->stream(
            fn () => print ($dompdf->output()),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    /**
     * Get the time range of the session.
     */
    private function sessionTime(ExamParticipant $participant): string
    {
        $session = $participant->session;

        if ($session === null) {
            return '-';
        }

        $start = substr((string) $session->start_time, 0, 5);
        $end = substr((string) $session->end_time, 0, 5);

        return "{$start} - {$end}";
    }

    /**
     * Normalize an optional string value.
     */
    private function nullableValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * Normalize an optional identifier value.
     */
    private function nullableId(mixed $value): ?int
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
