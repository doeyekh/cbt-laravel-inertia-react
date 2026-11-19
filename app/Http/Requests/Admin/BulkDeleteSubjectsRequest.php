<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkDeleteSubjectsRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('deleteAny', Subject::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('subjects', 'id'),
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
            'subject_ids.required' => 'Pilih minimal satu mata pelajaran.',
            'subject_ids.array' => 'Data mata pelajaran tidak valid.',
            'subject_ids.min' => 'Pilih minimal satu mata pelajaran.',
            'subject_ids.*.integer' => 'Data mata pelajaran tidak valid.',
            'subject_ids.*.distinct' => 'Mata pelajaran tidak boleh dipilih lebih dari sekali.',
            'subject_ids.*.exists' => 'Mata pelajaran tidak ditemukan.',
        ];
    }
}
