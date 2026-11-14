<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesQuestionBankScope;
use App\Models\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateQuestionBankRequest extends FormRequest
{
    use ValidatesQuestionBankScope;

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
        $questionBank = $this->route('question_bank');

        return [
            'school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')->whereNull('deleted_at'),
            ],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'exam_type_id' => ['nullable', 'integer', 'exists:exam_types,id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('question_banks', 'code')
                    ->where(fn ($query) => $query->where('school_id', $questionBank?->school_id))
                    ->ignore($questionBank),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'pilihan_ganda_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:500'],
            'pilihan_ganda_kompleks_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:500'],
            'menjodohkan_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:500'],
            'isian_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:500'],
            'esai_count' => ['sometimes', 'required', 'integer', 'min:0', 'max:500'],
            'pilihan_ganda_score' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999.99'],
            'pilihan_ganda_kompleks_score' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999.99'],
            'menjodohkan_score' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999.99'],
            'isian_score' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999.99'],
            'esai_score' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999.99'],
            'pilihan_ganda_option_count' => ['sometimes', 'required', 'integer', 'min:3', 'max:5'],
            'pilihan_ganda_kompleks_option_count' => ['sometimes', 'required', 'integer', 'min:3', 'max:5'],
            'class_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'exists:classes,id'],
            'is_active' => ['sometimes', 'boolean'],
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
                if ($validator->errors()->has('school_id')) {
                    return;
                }

                $this->validateQuestionBankScope($validator);

                $schoolId = (int) $this->input('school_id');
                $questionBank = $this->route('question_bank');

                if ($this->filled('exam_type_id')) {
                    $examType = ExamType::query()->find((int) $this->input('exam_type_id'));

                    if ($examType === null || (int) $examType->school_id !== $schoolId) {
                        $validator->errors()->add('exam_type_id', 'Jenis ujian harus berasal dari sekolah yang dipilih.');
                    } elseif (! $examType->is_active && (int) $examType->id !== (int) $questionBank?->exam_type_id) {
                        $validator->errors()->add('exam_type_id', 'Jenis ujian yang dipilih tidak aktif.');
                    }
                }

                $countFields = [
                    'pilihan_ganda_count',
                    'pilihan_ganda_kompleks_count',
                    'menjodohkan_count',
                    'isian_count',
                    'esai_count',
                ];

                if ($validator->errors()->hasAny($countFields)) {
                    return;
                }

                $total = 0;

                foreach ($countFields as $field) {
                    $total += (int) ($this->input($field) ?? 0);
                }

                if ($total === 0) {
                    $validator->errors()->add('composition', 'Minimal satu jenis soal harus memiliki jumlah lebih dari 0.');
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
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'subject_id.exists' => 'Mata pelajaran tidak valid.',
            'exam_type_id.exists' => 'Jenis ujian tidak valid.',
            'code.required' => 'Kode bank soal wajib diisi.',
            'code.max' => 'Kode bank soal maksimal 30 karakter.',
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode bank soal sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama bank soal wajib diisi.',
            'name.max' => 'Nama bank soal maksimal 150 karakter.',
            'description.max' => 'Deskripsi maksimal 500 karakter.',
            'pilihan_ganda_count.required' => 'Jumlah soal pilihan ganda wajib diisi.',
            'pilihan_ganda_count.integer' => 'Jumlah soal pilihan ganda harus berupa angka.',
            'pilihan_ganda_count.min' => 'Jumlah soal pilihan ganda minimal 0.',
            'pilihan_ganda_kompleks_count.required' => 'Jumlah soal pilihan kompleks wajib diisi.',
            'pilihan_ganda_kompleks_count.integer' => 'Jumlah soal pilihan kompleks harus berupa angka.',
            'pilihan_ganda_kompleks_count.min' => 'Jumlah soal pilihan kompleks minimal 0.',
            'menjodohkan_count.required' => 'Jumlah soal menjodohkan wajib diisi.',
            'menjodohkan_count.integer' => 'Jumlah soal menjodohkan harus berupa angka.',
            'menjodohkan_count.min' => 'Jumlah soal menjodohkan minimal 0.',
            'isian_count.required' => 'Jumlah soal isian wajib diisi.',
            'isian_count.integer' => 'Jumlah soal isian harus berupa angka.',
            'isian_count.min' => 'Jumlah soal isian minimal 0.',
            'esai_count.required' => 'Jumlah soal esai wajib diisi.',
            'esai_count.integer' => 'Jumlah soal esai harus berupa angka.',
            'esai_count.min' => 'Jumlah soal esai minimal 0.',
            'pilihan_ganda_score.required' => 'Bobot soal pilihan ganda wajib diisi.',
            'pilihan_ganda_score.numeric' => 'Bobot soal pilihan ganda harus berupa angka.',
            'pilihan_ganda_score.min' => 'Bobot soal pilihan ganda minimal 0.',
            'pilihan_ganda_kompleks_score.required' => 'Bobot soal pilihan kompleks wajib diisi.',
            'pilihan_ganda_kompleks_score.numeric' => 'Bobot soal pilihan kompleks harus berupa angka.',
            'pilihan_ganda_kompleks_score.min' => 'Bobot soal pilihan kompleks minimal 0.',
            'menjodohkan_score.required' => 'Bobot soal menjodohkan wajib diisi.',
            'menjodohkan_score.numeric' => 'Bobot soal menjodohkan harus berupa angka.',
            'menjodohkan_score.min' => 'Bobot soal menjodohkan minimal 0.',
            'isian_score.required' => 'Bobot soal isian wajib diisi.',
            'isian_score.numeric' => 'Bobot soal isian harus berupa angka.',
            'isian_score.min' => 'Bobot soal isian minimal 0.',
            'esai_score.required' => 'Bobot soal esai wajib diisi.',
            'esai_score.numeric' => 'Bobot soal esai harus berupa angka.',
            'esai_score.min' => 'Bobot soal esai minimal 0.',
            'pilihan_ganda_option_count.required' => 'Jumlah pilihan jawaban wajib diisi.',
            'pilihan_ganda_option_count.integer' => 'Jumlah pilihan jawaban harus berupa angka.',
            'pilihan_ganda_option_count.min' => 'Jumlah pilihan jawaban minimal 3.',
            'pilihan_ganda_option_count.max' => 'Jumlah pilihan jawaban maksimal 5.',
            'pilihan_ganda_kompleks_option_count.required' => 'Jumlah pilihan jawaban wajib diisi.',
            'pilihan_ganda_kompleks_option_count.integer' => 'Jumlah pilihan jawaban harus berupa angka.',
            'pilihan_ganda_kompleks_option_count.min' => 'Jumlah pilihan jawaban minimal 3.',
            'pilihan_ganda_kompleks_option_count.max' => 'Jumlah pilihan jawaban maksimal 5.',
            'class_ids.required' => 'Minimal satu kelas wajib dipilih.',
            'class_ids.min' => 'Minimal satu kelas wajib dipilih.',
            'class_ids.*.exists' => 'Kelas yang dipilih tidak valid.',
        ];
    }
}
