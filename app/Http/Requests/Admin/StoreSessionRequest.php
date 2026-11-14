<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
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
            'school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('exam_sessions')
                    ->where(fn ($query) => $query->where('school_id', $this->input('school_id'))),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'start_time' => [
                'required',
                'date_format:H:i:s',
            ],
            'end_time' => [
                'required',
                'date_format:H:i:s',
                'after:start_time',
            ],
            'is_active' => ['boolean'],
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
            'school_id.required' => 'Sekolah wajib dipilih.',
            'school_id.exists' => 'Sekolah yang dipilih tidak valid.',
            'code.required' => 'Kode sesi wajib diisi.',
            'code.max' => 'Kode maksimal 20 karakter.',
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode sesi sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama sesi wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'start_time.required' => 'Waktu mulai wajib diisi.',
            'start_time.date_format' => 'Format waktu mulai tidak valid (HH:MM).',
            'end_time.required' => 'Waktu selesai wajib diisi.',
            'end_time.date_format' => 'Format waktu selesai tidak valid (HH:MM).',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'is_active.boolean' => 'Status aktif tidak valid.',
        ];
    }
}
