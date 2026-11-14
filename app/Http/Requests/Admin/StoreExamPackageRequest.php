<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExamPackageStatus;
use App\Models\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExamPackageRequest extends FormRequest
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
        $schoolId = (int) $this->input('school_id');

        return [
            'school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')->whereNull('deleted_at'),
            ],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('exam_packages', 'code')
                    ->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(ExamPackageStatus::class)],
        ];
    }

    /**
     * Add validation rules that depend on the selected school.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('school_id') || $validator->errors()->has('exam_type_id')) {
                    return;
                }

                $examType = ExamType::query()->find((int) $this->input('exam_type_id'));

                if ($examType === null || (int) $examType->school_id !== (int) $this->input('school_id')) {
                    $validator->errors()->add('exam_type_id', 'Jenis ujian harus berasal dari sekolah yang dipilih.');

                    return;
                }

                if (! $examType->is_active) {
                    $validator->errors()->add('exam_type_id', 'Jenis ujian yang dipilih tidak aktif.');

                    if (ExamPackageStatus::tryFrom((string) $this->input('status')) !== ExamPackageStatus::Draft) {
                        $validator->errors()->add('status', 'Jenis ujian belum aktif, paket ujian hanya dapat berstatus draft.');
                    }
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
            'school_id.exists' => 'Sekolah yang dipilih tidak valid.',
            'academic_year_id.required' => 'Belum ada tahun pelajaran aktif.',
            'academic_year_id.exists' => 'Tahun pelajaran tidak valid.',
            'exam_type_id.required' => 'Jenis ujian wajib dipilih.',
            'exam_type_id.exists' => 'Jenis ujian tidak valid.',
            'code.required' => 'Kode paket wajib diisi.',
            'code.max' => 'Kode paket maksimal 30 karakter.',
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode paket sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama paket wajib diisi.',
            'name.max' => 'Nama paket maksimal 150 karakter.',
            'description.max' => 'Deskripsi maksimal 500 karakter.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date' => 'Tanggal mulai tidak valid.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.date' => 'Tanggal selesai tidak valid.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'status.required' => 'Status paket wajib dipilih.',
            'status.enum' => 'Status paket tidak valid.',
        ];
    }
}
