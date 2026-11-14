<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassRequest extends FormRequest
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
        $schoolId = (int) $this->input('school_id');
        $academicYearId = (int) (AcademicYear::query()->where('is_active', true)->value('id') ?? 0);

        return [
            'school_id' => [
                'required',
                'integer',
                Rule::in($this->user()->accessibleSchoolIds()),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('classes', 'code')->where(
                    fn ($query) => $query->where('school_id', $schoolId)
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('classes', 'name')->where(
                    fn ($query) => $query
                        ->where('school_id', $schoolId)
                        ->where('academic_year_id', $academicYearId)
                ),
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
            'school_id.integer' => 'Sekolah tidak valid.',
            'school_id.in' => 'Sekolah tidak valid.',
            'code.required' => 'Kode kelas wajib diisi.',
            'code.max' => 'Kode maksimal 20 karakter.',
            'code.regex' => 'Kode hanya berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode kelas sudah terdaftar untuk sekolah ini.',
            'name.required' => 'Nama kelas wajib diisi.',
            'name.max' => 'Nama kelas maksimal 100 karakter.',
            'name.unique' => 'Nama kelas sudah terdaftar untuk tahun pelajaran ini.',
        ];
    }

    /**
     * Get the school the class will be attached to.
     */
    public function schoolId(): int
    {
        return (int) $this->validated('school_id');
    }
}
