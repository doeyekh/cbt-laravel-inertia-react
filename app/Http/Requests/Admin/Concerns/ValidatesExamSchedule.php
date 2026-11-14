<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\ExamPackageStatus;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\QuestionBank;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Session;
use Illuminate\Validation\Validator;

trait ValidatesExamSchedule
{
    /**
     * Normalize the submitted opening and closing times before validation.
     */
    protected function prepareForValidation(): void
    {
        $normalizedTimes = [];

        foreach (['open_time', 'close_time'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && preg_match('/^\d{1,2}:\d{2}$/', $value) === 1) {
                $normalizedTimes[$field] = $value.':00';
            }
        }

        if ($normalizedTimes !== []) {
            $this->merge($normalizedTimes);
        }
    }

    /**
     * Get the base validation rules for an exam schedule.
     *
     * @return array<string, mixed>
     */
    protected function examScheduleRules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'exam_date' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'open_time' => ['required', 'date_format:H:i:s'],
            'close_time' => ['required', 'date_format:H:i:s', 'after:open_time'],
            'shuffle_questions' => ['required', 'boolean'],
            'shuffle_options' => ['required', 'boolean'],
            'show_score_to_student' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'class_id.required' => 'Kelas wajib dipilih.',
            'class_id.exists' => 'Kelas tidak valid.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'subject_id.exists' => 'Mata pelajaran tidak valid.',
            'session_id.required' => 'Sesi wajib dipilih.',
            'session_id.exists' => 'Sesi tidak valid.',
            'room_id.required' => 'Ruangan wajib dipilih.',
            'room_id.exists' => 'Ruangan tidak valid.',
            'exam_date.required' => 'Tanggal ujian wajib diisi.',
            'exam_date.date' => 'Tanggal ujian tidak valid.',
            'duration_minutes.required' => 'Durasi ujian wajib diisi.',
            'duration_minutes.integer' => 'Durasi ujian harus berupa angka.',
            'duration_minutes.min' => 'Durasi ujian minimal 1 menit.',
            'duration_minutes.max' => 'Durasi ujian maksimal 600 menit.',
            'open_time.required' => 'Jam soal dibuka wajib diisi.',
            'open_time.date_format' => 'Jam soal dibuka tidak valid.',
            'close_time.required' => 'Jam soal ditutup wajib diisi.',
            'close_time.date_format' => 'Jam soal ditutup tidak valid.',
            'close_time.after' => 'Jam soal ditutup harus setelah jam soal dibuka.',
            'shuffle_questions.required' => 'Pengaturan acak soal wajib dipilih.',
            'shuffle_questions.boolean' => 'Pengaturan acak soal tidak valid.',
            'shuffle_options.required' => 'Pengaturan acak pilihan jawaban wajib dipilih.',
            'shuffle_options.boolean' => 'Pengaturan acak pilihan jawaban tidak valid.',
            'show_score_to_student.required' => 'Pengaturan tampil nilai wajib dipilih.',
            'show_score_to_student.boolean' => 'Pengaturan tampil nilai tidak valid.',
        ];
    }

    /**
     * Validate the schedule against the exam package context.
     */
    protected function validateExamSchedule(Validator $validator, ExamPackage $examPackage, ?ExamSchedule $ignore = null): void
    {
        $fields = ['class_id', 'subject_id', 'session_id', 'room_id', 'exam_date'];

        foreach ($fields as $field) {
            if ($validator->errors()->has($field)) {
                return;
            }
        }

        if ($examPackage->status === ExamPackageStatus::Selesai) {
            $validator->errors()->add('exam_package', 'Jadwal tidak dapat diubah karena paket ujian sudah selesai.');

            return;
        }

        $schoolClass = SchoolClass::query()->find((int) $this->input('class_id'));
        $session = Session::query()->find((int) $this->input('session_id'));
        $room = Room::query()->find((int) $this->input('room_id'));

        if ($schoolClass === null
            || (int) $schoolClass->school_id !== (int) $examPackage->school_id
            || (int) $schoolClass->academic_year_id !== (int) $examPackage->academic_year_id) {
            $validator->errors()->add('class_id', 'Kelas harus berasal dari sekolah dan tahun pelajaran paket ujian.');
        }

        if ($session === null || (int) $session->school_id !== (int) $examPackage->school_id) {
            $validator->errors()->add('session_id', 'Sesi harus berasal dari sekolah paket ujian.');
        }

        if ($room === null || (int) $room->school_id !== (int) $examPackage->school_id) {
            $validator->errors()->add('room_id', 'Ruangan harus berasal dari sekolah paket ujian.');
        }

        if ($validator->errors()->has('class_id')
            || $validator->errors()->has('session_id')
            || $validator->errors()->has('room_id')) {
            return;
        }

        $questionBankQuery = QuestionBank::query()
            ->where('school_id', $examPackage->school_id)
            ->where('academic_year_id', $examPackage->academic_year_id)
            ->where('exam_type_id', $examPackage->exam_type_id)
            ->where('subject_id', (int) $this->input('subject_id'));

        if (! (clone $questionBankQuery)->exists()) {
            $validator->errors()->add('subject_id', 'Mata pelajaran belum memiliki bank soal untuk jenis ujian paket ini.');

            return;
        }

        if (! (clone $questionBankQuery)
            ->whereHas('classes', fn ($query) => $query->whereKey((int) $this->input('class_id')))
            ->exists()) {
            $validator->errors()->add('class_id', 'Bank soal mata pelajaran ini belum ditetapkan untuk kelas tersebut.');

            return;
        }

        $examDate = (string) $this->input('exam_date');

        if ($examDate < $examPackage->start_date->toDateString()
            || $examDate > $examPackage->end_date->toDateString()) {
            $validator->errors()->add('exam_date', 'Tanggal ujian harus berada di dalam periode paket ujian.');

            return;
        }

        $packageQuery = ExamSchedule::query()
            ->where('exam_package_id', $examPackage->id)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->id));

        $duplicateClassSubject = (clone $packageQuery)
            ->where('class_id', (int) $this->input('class_id'))
            ->where('subject_id', (int) $this->input('subject_id'))
            ->exists();

        if ($duplicateClassSubject) {
            $validator->errors()->add('subject_id', 'Mata pelajaran tersebut sudah dijadwalkan untuk kelas ini di paket yang sama.');
        }

        $schoolQuery = ExamSchedule::query()
            ->whereHas('examPackage', fn ($query) => $query->where('school_id', $examPackage->school_id))
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->id));

        $roomConflict = (clone $schoolQuery)
            ->whereDate('exam_date', $examDate)
            ->where('session_id', (int) $this->input('session_id'))
            ->where('room_id', (int) $this->input('room_id'))
            ->exists();

        if ($roomConflict) {
            $validator->errors()->add('room_id', 'Ruangan sudah dipakai pada tanggal dan sesi tersebut.');
        }

        $classConflict = (clone $schoolQuery)
            ->whereDate('exam_date', $examDate)
            ->where('session_id', (int) $this->input('session_id'))
            ->where('class_id', (int) $this->input('class_id'))
            ->exists();

        if ($classConflict) {
            $validator->errors()->add('class_id', 'Kelas sudah memiliki jadwal lain pada tanggal dan sesi tersebut.');
        }
    }
}
