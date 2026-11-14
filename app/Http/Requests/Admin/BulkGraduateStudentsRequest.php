<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkGraduateStudentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isAdministrator() || $user->isSchoolAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
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
            'student_ids.required' => 'Pilih minimal satu siswa.',
            'student_ids.array' => 'Data siswa tidak valid.',
            'student_ids.min' => 'Pilih minimal satu siswa.',
            'student_ids.*.integer' => 'Data siswa tidak valid.',
            'student_ids.*.distinct' => 'Data siswa tidak boleh duplikat.',
            'student_ids.*.exists' => 'Siswa tidak ditemukan.',
        ];
    }
}
