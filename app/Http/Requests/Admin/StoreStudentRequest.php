<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->canManage($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'nisn' => ['nullable', 'string', 'max:20', 'unique:students,nisn'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'parent_address' => ['nullable', 'string', 'max:1000'],
            'parent_bio' => ['nullable', 'string', 'max:2000'],
            'nis' => [
                'required',
                'string',
                'max:30',
                Rule::unique('student_registrations', 'nis')
                    ->where('school_id', $this->input('school_id')),
            ],
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
            'nisn.max' => 'NISN maksimal 20 karakter.',
            'nisn.unique' => 'NISN sudah terdaftar pada siswa lain.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.enum' => 'Jenis kelamin tidak valid.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'photo.image' => 'Foto harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.max' => 'NIS maksimal 30 karakter.',
            'nis.unique' => 'NIS sudah digunakan di sekolah tersebut.',
            'status.required' => 'Status registrasi wajib dipilih.',
            'status.enum' => 'Status registrasi tidak valid.',
            'exit_academic_year_id.required' => 'Tahun keluar wajib dipilih ketika status registrasi tidak aktif.',
            'exit_academic_year_id.exists' => 'Tahun keluar tidak valid.',
        ];
    }

    private function canManage(?User $user): bool
    {
        return $user !== null && ($user->isAdministrator() || $user->isSchoolAdmin());
    }
}
