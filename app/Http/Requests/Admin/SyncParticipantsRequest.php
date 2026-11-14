<?php

namespace App\Http\Requests\Admin;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncParticipantsRequest extends FormRequest
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
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'distinct', 'exists:classes,id'],
        ];
    }

    /**
     * Add validation rules that depend on the selected classes.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['school_id', 'academic_year_id', 'class_ids'])) {
                    return;
                }

                $schoolId = (int) $this->input('school_id');
                $academicYearId = (int) $this->input('academic_year_id');
                $classIds = array_map('intval', (array) $this->input('class_ids'));

                $validClassIds = SchoolClass::query()
                    ->where('school_id', $schoolId)
                    ->where('academic_year_id', $academicYearId)
                    ->whereIn('id', $classIds)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                $invalid = array_diff($classIds, $validClassIds);

                if ($invalid !== []) {
                    $validator->errors()->add(
                        'class_ids',
                        'Semua kelas harus berasal dari sekolah dan tahun pelajaran yang dipilih.',
                    );
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
            'academic_year_id.required' => 'Tahun pelajaran wajib dipilih.',
            'academic_year_id.exists' => 'Tahun pelajaran tidak valid.',
            'class_ids.required' => 'Pilih minimal satu kelas.',
            'class_ids.min' => 'Pilih minimal satu kelas.',
            'class_ids.array' => 'Pilihan kelas tidak valid.',
            'class_ids.*.exists' => 'Ada kelas yang tidak valid.',
            'class_ids.*.distinct' => 'Ada kelas yang dipilih lebih dari sekali.',
        ];
    }
}
