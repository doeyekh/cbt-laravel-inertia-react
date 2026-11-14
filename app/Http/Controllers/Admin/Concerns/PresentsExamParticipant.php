<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Enums\RegistrationStatus;
use App\Models\ExamParticipant;
use App\Models\School;

trait PresentsExamParticipant
{
    /**
     * Transform an exam participant into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentExamParticipant(ExamParticipant $participant): array
    {
        $registrations = $participant->student?->registrations ?? collect();

        $registration = $registrations->first(
            fn ($item): bool => (int) $item->school_id === (int) $participant->school_id
                && $item->status === RegistrationStatus::Aktif,
        ) ?? $registrations->firstWhere('status', RegistrationStatus::Aktif)
            ?? $registrations->firstWhere('school_id', (int) $participant->school_id)
            ?? $registrations->first();

        $classMember = $participant->student?->classMembers
            ?->firstWhere('academic_year_id', $participant->academic_year_id);

        return [
            'id' => $participant->id,
            'student_id' => $participant->student_id,
            'school_id' => $participant->school_id,
            'academic_year_id' => $participant->academic_year_id,
            'student_name' => $participant->student?->name,
            'student_nisn' => $participant->student?->nisn,
            'student_nis' => $registration?->nis,
            'class_id' => $classMember?->class_id,
            'class_name' => $classMember?->schoolClass?->name,
            'session_id' => $participant->session_id,
            'session_code' => $participant->session?->code,
            'session_name' => $participant->session?->name,
            'room_id' => $participant->room_id,
            'room_code' => $participant->room?->code,
            'room_name' => $participant->room?->name,
            'academic_year_code' => $participant->academicYear?->code,
            'has_placement' => $participant->hasPlacement(),
            'username' => $participant->username,
            'participant_number' => $participant->participant_number,
            'password' => $participant->password_plain,
            'has_credential' => $participant->hasCredential(),
        ];
    }

    /**
     * Transform the credential template of a school into its frontend representation.
     *
     * @return array<string, mixed>
     */
    private function presentCredentialTemplate(School $school): array
    {
        return [
            'school_id' => (int) $school->id,
            'school_name' => $school->name,
            'school_code' => $school->code,
            'username_template' => $school->username_template,
            'participant_number_template' => $school->participant_number_template,
            'number_width' => (int) $school->number_width,
        ];
    }
}
