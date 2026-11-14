<?php

namespace App\Http\Requests\Admin;

use App\Enums\ExamPackageStatus;
use App\Models\ExamPackage;
use App\Models\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateExamPackageRequest extends FormRequest
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
        $examPackage = $this->route('exam_package');

        return [
            'exam_type_id' => ['sometimes', 'required', 'integer', 'exists:exam_types,id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('exam_packages', 'code')
                    ->where(fn ($query) => $query->where('school_id', $examPackage?->school_id))
                    ->ignore($examPackage),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', Rule::enum(ExamPackageStatus::class)],
        ];
    }

    /**
     * Add validation rules that depend on the package context.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $examPackage = $this->route('exam_package');

                if (! $examPackage instanceof ExamPackage) {
                    return;
                }

                $startDate = (string) ($this->input('start_date') ?? $examPackage->start_date->toDateString());
                $endDate = (string) ($this->input('end_date') ?? $examPackage->end_date->toDateString());

                if ($endDate < $startDate) {
                    $validator->errors()->add('end_date', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
                } elseif (! $validator->errors()->has('start_date') && ! $validator->errors()->has('end_date')) {
                    $hasOutsideSchedule = $examPackage->schedules()
                        ->where(function ($query) use ($startDate, $endDate): void {
                            $query->whereDate('exam_date', '<', $startDate)
                                ->orWhereDate('exam_date', '>', $endDate);
                        })
                        ->exists();

                    if ($hasOutsideSchedule) {
                        $validator->errors()->add('end_date', 'Periode paket tidak boleh mengecualikan jadwal yang sudah ada.');
                    }
                }

                if (! $validator->errors()->has('exam_type_id') && $this->has('exam_type_id')) {
                    $examType = ExamType::query()->find((int) $this->input('exam_type_id'));

                    if ($examType === null || (int) $examType->school_id !== (int) $examPackage->school_id) {
                        $validator->errors()->add('exam_type_id', 'Jenis ujian harus berasal dari sekolah paket.');
                    } elseif (! $examType->is_active && (int) $examType->id !== (int) $examPackage->exam_type_id) {
                        $validator->errors()->add('exam_type_id', 'Jenis ujian yang dipilih tidak aktif.');
                    }
                }

                $effectiveStatus = ExamPackageStatus::tryFrom(
                    (string) ($this->input('status') ?? $examPackage->status->value)
                );
                $effectiveExamType = ExamType::query()->find(
                    (int) ($this->input('exam_type_id') ?? $examPackage->exam_type_id)
                );

                if ($effectiveStatus !== null
                    && $effectiveStatus !== ExamPackageStatus::Draft
                    && $effectiveExamType !== null
                    && ! $effectiveExamType->is_active) {
                    $validator->errors()->add('status', 'Jenis ujian belum aktif, paket ujian hanya dapat berstatus draft.');
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
            'status.required' => 'Status paket wajib dipilih.',
            'status.enum' => 'Status paket tidak valid.',
        ];
    }
}
