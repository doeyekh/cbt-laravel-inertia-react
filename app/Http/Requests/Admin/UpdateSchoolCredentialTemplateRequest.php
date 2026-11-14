<?php

namespace App\Http\Requests\Admin;

use App\Services\ExamParticipantCredentialService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSchoolCredentialTemplateRequest extends FormRequest
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
            'username_template' => ['required', 'string', 'max:120'],
            'participant_number_template' => ['required', 'string', 'max:120'],
            'number_width' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }

    /**
     * Add validation rules that depend on the credential template itself.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (['username_template', 'participant_number_template'] as $field) {
                    $template = trim((string) $this->input($field));

                    if ($template === '' || $validator->errors()->has($field)) {
                        continue;
                    }

                    preg_match_all('/\{([a-z]+)\}/', $template, $matches);

                    $placeholders = $matches[1] ?? [];

                    if ($placeholders === []) {
                        $validator->errors()->add($field, 'Template harus memuat minimal satu placeholder, contoh {nis}.');

                        continue;
                    }

                    $unknown = array_diff($placeholders, ExamParticipantCredentialService::PLACEHOLDERS);

                    foreach ($unknown as $placeholder) {
                        $validator->errors()->add($field, "Placeholder {{$placeholder}} tidak dikenal.");
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
            'username_template.required' => 'Template username wajib diisi.',
            'username_template.max' => 'Template username maksimal 120 karakter.',
            'participant_number_template.required' => 'Template nomor peserta wajib diisi.',
            'participant_number_template.max' => 'Template nomor peserta maksimal 120 karakter.',
            'number_width.required' => 'Lebar nomor wajib diisi.',
            'number_width.integer' => 'Lebar nomor harus berupa angka.',
            'number_width.min' => 'Lebar nomor minimal 1.',
            'number_width.max' => 'Lebar nomor maksimal 6.',
        ];
    }
}
