<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamTypeRequest extends FormRequest
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
        $examType = $this->route('exam_type');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('exam_types')
                    ->where(fn ($query) => $query->where('school_id', $examType?->school_id))
                    ->ignore($examType),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'description' => [
                'sometimes',
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
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode jenis ujian sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama jenis ujian wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'description.max' => 'Deskripsi maksimal 255 karakter.',
        ];
    }
}
