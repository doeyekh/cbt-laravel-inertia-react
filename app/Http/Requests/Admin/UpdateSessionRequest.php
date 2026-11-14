<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionRequest extends FormRequest
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
        $session = $this->route('session');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('exam_sessions')
                    ->where(fn ($query) => $query->where('school_id', $session?->school_id))
                    ->ignore($session),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'start_time' => [
                'sometimes',
                'required',
                'date_format:H:i:s',
            ],
            'end_time' => [
                'sometimes',
                'required',
                'date_format:H:i:s',
                'after:start_time',
            ],
            'is_active' => ['sometimes', 'boolean'],
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
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode sesi sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama sesi wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'start_time.date_format' => 'Format waktu mulai tidak valid (HH:MM).',
            'end_time.date_format' => 'Format waktu selesai tidak valid (HH:MM).',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'is_active.boolean' => 'Status aktif tidak valid.',
        ];
    }
}
