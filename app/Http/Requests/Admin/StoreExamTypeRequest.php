<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamTypeRequest extends FormRequest
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
                Rule::unique('exam_types')
                    ->where(fn ($query) => $query->where('school_id', $this->input('school_id'))),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
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
            'code.required' => 'Kode jenis ujian wajib diisi.',
            'code.max' => 'Kode maksimal 20 karakter.',
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode jenis ujian sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama jenis ujian wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'description.max' => 'Deskripsi maksimal 255 karakter.',
        ];
    }
}
