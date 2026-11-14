<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentTypeRequest extends FormRequest
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
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('assessment_types')
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
            'weight' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
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
            'code.required' => 'Kode jenis penilaian wajib diisi.',
            'code.max' => 'Kode maksimal 20 karakter.',
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, garis bawah, dan tanda hubung.',
            'code.unique' => 'Kode jenis penilaian sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama jenis penilaian wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'description.max' => 'Deskripsi maksimal 255 karakter.',
            'weight.required' => 'Bobot nilai wajib diisi.',
            'weight.numeric' => 'Bobot nilai harus berupa angka.',
            'weight.min' => 'Bobot nilai minimal 0%.',
            'weight.max' => 'Bobot nilai maksimal 100%.',
        ];
    }
}
