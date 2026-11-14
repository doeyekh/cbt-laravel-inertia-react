<?php

namespace App\Http\Requests\Admin;

use App\Enums\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentRegistrationRequest extends FormRequest
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
        $studentId = $this->route('student')?->id;
        $registrationId = $this->route('registration')?->id;

        return [
            'school_id' => [
                'required',
                'integer',
                'exists:schools,id',
                Rule::unique('student_registrations', 'school_id')
                    ->where('student_id', $studentId)
                    ->ignore($registrationId),
            ],
            'nis' => [
                'required',
                'string',
                'max:30',
                Rule::unique('student_registrations', 'nis')
                    ->where('school_id', $this->input('school_id'))
                    ->ignore($registrationId),
            ],
            'entry_academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'status' => ['required', Rule::enum(RegistrationStatus::class)],
            'exit_academic_year_id' => [
                'nullable',
                'integer',
                'exists:academic_years,id',
                Rule::requiredIf(fn (): bool => $this->input('status') !== RegistrationStatus::Aktif->value),
            ],
        ];
    }

    /**
     * Add validation rules that depend on the authenticated user's access.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('school_id')) {
                    return;
                }

                $user = $this->user();

                if ($user === null || ! in_array((int) $this->input('school_id'), $user->accessibleSchoolIds(), true)) {
                    $validator->errors()->add('school_id', 'Anda tidak memiliki akses ke sekolah tersebut.');
                }
            },
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
            'school_id.exists' => 'Sekolah tidak valid.',
            'school_id.unique' => 'Siswa sudah terdaftar di sekolah tersebut.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.max' => 'NIS maksimal 30 karakter.',
            'nis.unique' => 'NIS sudah digunakan di sekolah tersebut.',
            'entry_academic_year_id.required' => 'Tahun masuk wajib dipilih.',
            'entry_academic_year_id.exists' => 'Tahun masuk tidak valid.',
            'status.required' => 'Status registrasi wajib dipilih.',
            'status.enum' => 'Status registrasi tidak valid.',
            'exit_academic_year_id.required' => 'Tahun keluar wajib dipilih ketika status registrasi tidak aktif.',
            'exit_academic_year_id.exists' => 'Tahun keluar tidak valid.',
        ];
    }
}
