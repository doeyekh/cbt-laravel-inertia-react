<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkUpdateExamScheduleRequest;
use App\Http\Requests\Admin\StoreExamScheduleRequest;
use App\Http\Requests\Admin\UpdateExamScheduleRequest;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\Room;
use App\Models\Session;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamScheduleController extends Controller
{
    /**
     * Store a newly created exam schedule.
     */
    public function store(StoreExamScheduleRequest $request, ExamPackage $examPackage): RedirectResponse
    {
        $this->authorizeScheduleManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);

        $examSchedule = DB::transaction(
            fn(): ExamSchedule => $examPackage->schedules()->create($request->validated())
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Jadwal Ditambahkan',
            'description' => "Jadwal {$examSchedule->subject?->name} untuk {$examSchedule->schoolClass?->name} telah ditambahkan.",
        ]);
    }

    /**
     * Update the specified exam schedule.
     */
    public function update(
        UpdateExamScheduleRequest $request,
        ExamPackage $examPackage,
        ExamSchedule $examSchedule,
    ): RedirectResponse {
        $this->authorizeScheduleManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);
        $this->ensureScheduleBelongsToPackage($examSchedule, $examPackage);

        $examSchedule->update($request->validated());

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Jadwal Diperbarui',
            'description' => "Jadwal {$examSchedule->subject?->name} untuk {$examSchedule->schoolClass?->name} telah diperbarui.",
        ]);
    }

    /**
     * Remove the specified exam schedule.
     */
    public function destroy(
        Request $request,
        ExamPackage $examPackage,
        ExamSchedule $examSchedule,
    ): RedirectResponse {
        $this->authorizeScheduleManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);
        $this->ensureScheduleBelongsToPackage($examSchedule, $examPackage);

        $examSchedule->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Jadwal Dihapus',
            'description' => 'Jadwal ujian telah dihapus dari paket ini.',
        ]);
    }

    /**
     * Toggle the active status of the specified exam schedule.
     */
    public function toggleStatus(
        Request $request,
        ExamPackage $examPackage,
        ExamSchedule $examSchedule,
    ): RedirectResponse {
        $this->authorizeScheduleManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);
        $this->ensureScheduleBelongsToPackage($examSchedule, $examPackage);

        if ($examPackage->status === ExamPackageStatus::Selesai) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal Mengubah Status',
                'description' => 'Status jadwal tidak dapat diubah karena paket ujian sudah selesai.',
            ]);
        }

        $nextStatus = $examSchedule->status === ExamScheduleStatus::Aktif
            ? ExamScheduleStatus::Nonaktif
            : ExamScheduleStatus::Aktif;

        $examSchedule->update(['status' => $nextStatus]);

        $isActive = $nextStatus === ExamScheduleStatus::Aktif;

        return back()->with('toast', [
            'type' => 'success',
            'title' => $isActive ? 'Jadwal Diaktifkan' : 'Jadwal Dinonaktifkan',
            'description' => "Jadwal {$examSchedule->subject?->name} untuk {$examSchedule->schoolClass?->name} sekarang " . ($isActive ? 'aktif' : 'nonaktif') . '.',
        ]);
    }

    /**
     * Set the session and room for multiple exam schedules at once.
     */
    public function bulkUpdate(
        BulkUpdateExamScheduleRequest $request,
        ExamPackage $examPackage,
    ): RedirectResponse {
        $this->authorizeScheduleManage($request->user());
        $this->authorizeRecordAccess($request->user(), $examPackage);

        $data = $request->validated();

        $sessionId = $data['session_id'] ?? null;
        $roomId = $data['room_id'] ?? null;

        $session = null;
        $room = null;

        if ($sessionId !== null) {
            $session = Session::query()->find((int) $sessionId);

            if ($session === null || (int) $session->school_id !== (int) $examPackage->school_id) {
                return back()->withErrors(['session_id' => 'Sesi harus berasal dari sekolah paket ujian.']);
            }
        }

        if ($roomId !== null) {
            $room = Room::query()->find((int) $roomId);

            if ($room === null || (int) $room->school_id !== (int) $examPackage->school_id) {
                return back()->withErrors(['room_id' => 'Ruangan harus berasal dari sekolah paket ujian.']);
            }
        }

        $attributes = [];

        if ($sessionId !== null) {
            $attributes['session_id'] = $sessionId;
        }

        if ($roomId !== null) {
            $attributes['room_id'] = $roomId;
        }

        $count = DB::transaction(function () use ($examPackage, $data, $attributes): int {
            return ExamSchedule::query()
                ->where('exam_package_id', $examPackage->id)
                ->whereIn('id', $data['schedule_ids'])
                ->update($attributes);
        });

        if ($count === 0) {
            return back()->withErrors([
                'schedule_ids' => 'Jadwal ujian yang dipilih tidak ditemukan pada paket ini.',
            ]);
        }

        $parts = [];

        if ($session !== null) {
            $parts[] = "sesi {$session->name}";
        }

        if ($room !== null) {
            $parts[] = "ruangan {$room->name}";
        }

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Sesi dan Ruang Diperbarui',
            'description' => "{$count} jadwal ujian telah diperbarui dengan " . implode(' dan ', $parts) . '.',
        ]);
    }

    /**
     * Ensure the given schedule belongs to the package in the route.
     */
    private function ensureScheduleBelongsToPackage(ExamSchedule $examSchedule, ExamPackage $examPackage): void
    {
        if ((int) $examSchedule->exam_package_id !== (int) $examPackage->id) {
            abort(404);
        }
    }

    /**
     * Determine whether the user may make changes to exam schedules.
     */
    private function authorizeScheduleManage(?User $user): void
    {
        if ($user !== null && ($user->isAdministrator() || $user->isSchoolAdmin())) {
            return;
        }

        abort(403);
    }

    /**
     * Determine whether the user may access the given exam package.
     */
    private function authorizeRecordAccess(User $user, ExamPackage $examPackage): void
    {
        if (! in_array((int) $examPackage->school_id, $user->accessibleSchoolIds(), true)) {
            abort(403);
        }
    }


    public function printAttendance(Request $request, ExamSchedule $schedule): StreamedResponse
    {
        $this->authorizeScheduleManage($request->user());

        // Load relasi yang diperlukan termasuk school lewat examPackage
        $schedule->load([
            'examPackage.school',
            'examPackage.academicYear',
            'examPackage.examType',
            'subject',
            'schoolClass',
            'session',
            'room',
        ]);

        $this->authorizeRecordAccess(
            $request->user(),
            $schedule->examPackage
        );

        // Ambil peserta berdasarkan room_id dan session_id dari jadwal
        $participants = ExamParticipant::query()
            ->where('school_id', $schedule->examPackage->school_id)
            ->where('room_id', $schedule->room_id)
            ->where('session_id', $schedule->session_id)
            ->with(['student.registrations' => fn($query) => $query->where('school_id', $schedule->examPackage->school_id)])
            ->orderBy('participant_number')
            ->get()
            ->map(function (ExamParticipant $participant): array {
                return [
                    'username' => $participant->username ?? '-',
                    'participant_number' => $participant->participant_number ?? '-',
                    'name' => $participant->student?->name ?? '-',
                ];
            })
            ->values()
            ->all();

        // Konfigurasi Dompdf (menyamakan dengan method cards)
        $options = new Options();
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

        // Render view Blade menjadi HTML
        $html = view('exam-packages.print-attendance', [
            'examPackage' => $schedule->examPackage,
            'schedule' => $schedule,
            'participants' => $participants,
            'print_date' => now()->format('d/m/Y H:i'),
        ])->render();

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Daftar-Hadir-' . ($schedule->subject->name ?? 'Ujian') . '-' . now()->format('Ymd') . '.pdf';

        return response()->stream(
            fn() => print($dompdf->output()),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }
}
