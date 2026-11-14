<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamScheduleRequest;
use App\Http\Requests\Admin\UpdateExamScheduleRequest;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            fn (): ExamSchedule => $examPackage->schedules()->create($request->validated())
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
            'description' => "Jadwal {$examSchedule->subject?->name} untuk {$examSchedule->schoolClass?->name} sekarang ".($isActive ? 'aktif' : 'nonaktif').'.',
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
}
