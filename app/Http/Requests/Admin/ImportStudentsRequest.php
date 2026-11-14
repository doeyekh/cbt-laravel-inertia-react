<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ImportStudentsRequest extends FormRequest
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
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
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
            'file.required' => 'Berkas import wajib dipilih.',
            'file.file' => 'Berkas import tidak valid.',
            'file.extensions' => 'Berkas harus berformat XLSX, XLS, atau CSV.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
            'school_id.required' => 'Sekolah wajib dipilih.',
            'school_id.exists' => 'Sekolah tidak valid.',
        ];
    }
}
