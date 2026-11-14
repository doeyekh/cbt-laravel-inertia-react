<?php

namespace App\Http\Requests\Admin;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
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
        /** @var SchoolClass $schoolClass */
        $schoolClass = $this->route('schoolClass');

        $schoolId = (int) $schoolClass->school_id;
        $academicYearId = (int) $schoolClass->academic_year_id;

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('classes', 'code')
                    ->where(fn ($query) => $query->where('school_id', $schoolId))
                    ->ignore($schoolClass),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('classes', 'name')
                    ->where(fn ($query) => $query
                        ->where('school_id', $schoolId)
                        ->where('academic_year_id', $academicYearId))
                    ->ignore($schoolClass),
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
            'code.required' => 'Kode kelas wajib diisi.',
            'code.max' => 'Kode maksimal 20 karakter.',
            'code.regex' => 'Kode hanya berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode kelas sudah terdaftar untuk sekolah ini.',
            'name.required' => 'Nama kelas wajib diisi.',
            'name.max' => 'Nama kelas maksimal 100 karakter.',
            'name.unique' => 'Nama kelas sudah terdaftar untuk tahun pelajaran ini.',
        ];
    }
}
