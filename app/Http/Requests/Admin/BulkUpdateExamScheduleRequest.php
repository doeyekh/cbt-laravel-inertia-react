<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateExamScheduleRequest extends FormRequest
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
            'schedule_ids' => ['required', 'array', 'min:1'],
            'schedule_ids.*' => ['integer', 'exists:exam_schedules,id'],
            'session_id' => ['nullable', 'integer', 'exists:exam_sessions,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
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
            'schedule_ids.required' => 'Pilih minimal satu jadwal ujian.',
            'schedule_ids.min' => 'Pilih minimal satu jadwal ujian.',
            'schedule_ids.*.exists' => 'Jadwal ujian tidak valid.',
            'session_id.exists' => 'Sesi tidak valid.',
            'room_id.exists' => 'Ruangan tidak valid.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('schedule_ids')) {
            $this->merge([
                'schedule_ids' => array_values(array_unique(array_map('intval', (array) $this->input('schedule_ids')))),
            ]);
        }
    }

    /**
     * Ensure at least one of session or room is being updated.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('session_id') && ! $this->filled('room_id')) {
                $validator->errors()->add(
                    'session_id',
                    'Pilih setidaknya sesi atau ruangan yang akan ditetapkan.'
                );
            }
        });
    }
}
