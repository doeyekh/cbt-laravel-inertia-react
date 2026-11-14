<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectTeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolClassId = $this->route('schoolClass')?->id;

        return [
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
                Rule::unique('subject_teachers', 'subject_id')
                    ->where('school_class_id', $schoolClassId),
            ],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', UserRole::Guru->value)
                    ->where('is_active', true),
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
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'subject_id.exists' => 'Mata pelajaran tidak valid.',
            'subject_id.unique' => 'Mata pelajaran ini sudah memiliki guru pengampuh di kelas tersebut.',
            'teacher_id.required' => 'Guru pengampuh wajib dipilih.',
            'teacher_id.exists' => 'Guru tidak valid atau bukan guru aktif.',
        ];
    }
}
