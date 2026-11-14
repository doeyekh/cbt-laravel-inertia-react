<?php

namespace App\Http\Requests\Admin;

use App\Enums\AcademicYearSemester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

class StoreAcademicYearRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:20',
                'regex:/^\d{4}\/\d{4}$/',
                $this->uniqueNameRule(),
            ],
            'semester' => [
                'required',
                Rule::enum(AcademicYearSemester::class),
            ],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $name = (string) $this->input('name');

                if (! preg_match('/^(\d{4})\/(\d{4})$/', $name, $matches)) {
                    return;
                }

                if ((int) $matches[2] !== (int) $matches[1] + 1) {
                    $validator->errors()->add(
                        'name',
                        'Tahun akhir harus satu tahun setelah tahun awal.'
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
            'name.required' => 'Nama tahun pelajaran wajib diisi.',
            'name.regex' => 'Nama tahun pelajaran harus berformat YYYY/YYYY, contoh 2024/2025.',
            'name.unique' => 'Tahun pelajaran dengan semester tersebut sudah terdaftar.',
            'semester.required' => 'Semester wajib dipilih.',
            'semester' => 'Semester harus ganjil atau genap.',
        ];
    }

    private function uniqueNameRule(): Unique
    {
        return Rule::unique('academic_years')
            ->where(fn ($query) => $query->where('semester', $this->input('semester')));
    }
}
