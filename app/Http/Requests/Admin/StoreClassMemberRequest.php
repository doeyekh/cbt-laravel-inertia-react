<?php

namespace App\Http\Requests\Admin;

use App\Enums\RegistrationStatus;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreClassMemberRequest extends FormRequest
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
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ];
    }

    /**
     * Add validation rules that depend on the class and the student's registration.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('student_id')) {
                    return;
                }

                $schoolClass = $this->route('schoolClass');

                if (! $schoolClass instanceof SchoolClass) {
                    return;
                }

                $studentId = (int) $this->input('student_id');

                $hasActiveRegistration = Student::query()
                    ->whereKey($studentId)
                    ->whereHas('registrations', function ($query) use ($schoolClass): void {
                        $query->where('school_id', $schoolClass->school_id)
                            ->where('status', RegistrationStatus::Aktif->value);
                    })
                    ->exists();

                if (! $hasActiveRegistration) {
                    $validator->errors()->add('student_id', 'Siswa tidak memiliki registrasi aktif di sekolah kelas ini.');

                    return;
                }

                $alreadyAssigned = ClassMember::query()
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $schoolClass->academic_year_id)
                    ->exists();

                if ($alreadyAssigned) {
                    $validator->errors()->add('student_id', 'Siswa sudah terdaftar di kelas lain pada tahun pelajaran ini.');
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
            'student_id.required' => 'Siswa wajib dipilih.',
            'student_id.exists' => 'Siswa tidak valid.',
        ];
    }
}
