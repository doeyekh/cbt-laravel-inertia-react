<?php

namespace App\Services;

use App\Enums\ExamPackageStatus;
use App\Enums\ExamScheduleStatus;
use App\Models\ClassMember;
use App\Models\ExamPackage;
use App\Models\ExamParticipant;
use App\Models\ExamSchedule;
use App\Models\QuestionBank;
use Illuminate\Support\Carbon;

class ExamScheduleAccessService
{
    /**
     * Determine whether the participant is assigned to the given schedule.
     *
     * Pemeriksaan mencakup sekolah, tahun pelajaran, sesi, ruangan, dan
     * keanggotaan kelas. Semua endpoint siswa memakai pemeriksaan yang sama
     * agar jadwal kelas lain tidak dapat dibuka dengan token paket yang sah.
     */
    public function participantMayAccess(
        ExamParticipant $participant,
        ExamSchedule $examSchedule,
        ?ExamPackage $examPackage = null,
    ): bool {
        $examPackage ??= $examSchedule->examPackage;

        if ($examPackage === null) {
            return false;
        }

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
     * Determine whether the package and schedule are both marked active.
     */
    public function isActive(ExamPackage $examPackage, ExamSchedule $examSchedule): bool
    {
        return $examPackage->status === ExamPackageStatus::Aktif
            && $examSchedule->status === ExamScheduleStatus::Aktif;
    }

    /**
     * Determine whether the schedule date falls within the package date range.
     */
    public function isWithinPackageDateRange(ExamPackage $examPackage, ExamSchedule $examSchedule): bool
    {
        $examDate = $examSchedule->exam_date?->toDateString();

        return $examDate !== null
            && $examDate >= $examPackage->start_date->toDateString()
            && $examDate <= $examPackage->end_date->toDateString();
    }

    /**
     * Determine whether the schedule is currently open for answering.
     */
    public function scheduleIsOpen(ExamPackage $examPackage, ExamSchedule $examSchedule): bool
    {
        if (! $this->isWithinPackageDateRange($examPackage, $examSchedule)) {
            return false;
        }

        $examDate = $examSchedule->exam_date->toDateString();
        $opensAt = Carbon::parse("{$examDate} {$examSchedule->open_time}");
        $closesAt = Carbon::parse("{$examDate} {$examSchedule->close_time}");
        $currentTime = now();

        return $currentTime->gte($opensAt) && $currentTime->lte($closesAt);
    }

    /**
     * Resolve the server-side deadline of a new attempt.
     *
     * Durasi pengerjaan tidak boleh melewati jam tutup jadwal ujian.
     */
    public function deadlineFor(ExamSchedule $examSchedule, Carbon $startedAt): Carbon
    {
        $examDate = $examSchedule->exam_date->toDateString();
        $closesAt = Carbon::parse("{$examDate} {$examSchedule->close_time}");
        $durationDeadline = $startedAt->copy()->addMinutes((int) $examSchedule->duration_minutes);

        return $durationDeadline->lt($closesAt) ? $durationDeadline : $closesAt;
    }

    /**
     * Resolve the question bank that belongs to the schedule's package.
     */
    public function resolveQuestionBank(ExamSchedule $examSchedule, ?ExamPackage $examPackage = null): ?QuestionBank
    {
        $examPackage ??= $examSchedule->examPackage;

        if ($examPackage === null) {
            return null;
        }

        return QuestionBank::query()
            ->where('school_id', $examPackage->school_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->where('exam_type_id', $examPackage->exam_type_id)
            ->where('subject_id', $examSchedule->subject_id)
            ->first();
    }
}
