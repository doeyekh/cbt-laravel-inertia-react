<?php

namespace App\Http\Requests\Admin;

use App\Models\ExamParticipant;
use App\Models\Room;
use App\Models\Session;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateExamParticipantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['nullable', 'string', 'max:50'],
            'participant_number' => ['nullable', 'string', 'max:30'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'session_id' => ['nullable', 'integer', 'exists:exam_sessions,id'],
        ];
    }

    /**
     * Add validation rules that depend on the participant context.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $participant = $this->route('examParticipant');

                if (! $participant instanceof ExamParticipant) {
                    return;
                }

                $this->validateUniqueCredential($validator, $participant);
                $this->validatePlacement($validator, $participant);
            },
        ];
    }

    /**
     * Pastikan ruangan dan sesi berasal dari sekolah peserta.
     */
    private function validatePlacement(Validator $validator, ExamParticipant $participant): void
    {
        $roomId = (int) $this->input('room_id');
        $sessionId = (int) $this->input('session_id');

        if ($roomId > 0 && ! $validator->errors()->has('room_id')) {
            $room = Room::query()->find($roomId);

            if ($room === null || (int) $room->school_id !== (int) $participant->school_id) {
                $validator->errors()->add('room_id', 'Ruangan harus berasal dari sekolah peserta.');
            }
        }

        if ($sessionId > 0 && ! $validator->errors()->has('session_id')) {
            $session = Session::query()->find($sessionId);

            if ($session === null || (int) $session->school_id !== (int) $participant->school_id) {
                $validator->errors()->add('session_id', 'Sesi harus berasal dari sekolah peserta.');
            }
        }
    }

    /**
     * Pastikan username dan nomor peserta unik dalam sekolah dan tahun pelajaran.
     */
    private function validateUniqueCredential(Validator $validator, ExamParticipant $participant): void
    {
        $username = trim((string) $this->input('username'));
        $participantNumber = trim((string) $this->input('participant_number'));

        if ($username !== '' && ExamParticipant::query()
            ->where('school_id', $participant->school_id)
            ->where('academic_year_id', $participant->academic_year_id)
            ->where('username', $username)
            ->whereKeyNot($participant->id)
            ->exists()) {
            $validator->errors()->add('username', 'Username sudah dipakai peserta lain pada tahun ini.');
        }

        if ($participantNumber !== '' && ExamParticipant::query()
            ->where('school_id', $participant->school_id)
            ->where('academic_year_id', $participant->academic_year_id)
            ->where('participant_number', $participantNumber)
            ->whereKeyNot($participant->id)
            ->exists()) {
            $validator->errors()->add('participant_number', 'Nomor peserta sudah dipakai peserta lain pada tahun ini.');
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.max' => 'Username maksimal 50 karakter.',
            'participant_number.max' => 'Nomor peserta maksimal 30 karakter.',
            'room_id.exists' => 'Ruangan tidak valid.',
            'session_id.exists' => 'Sesi tidak valid.',
        ];
    }
}
