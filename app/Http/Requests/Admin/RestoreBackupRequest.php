<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:RESTORE'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'confirmation.required' => 'Ketik RESTORE untuk mengonfirmasi.',
            'confirmation.in' => 'Ketikan konfirmasi harus tepat RESTORE.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('current_password')) {
                    return;
                }

                $matches = Hash::check(
                    $this->string('current_password')->toString(),
                    (string) $this->user()?->getAuthPassword()
                );

                if (! $matches) {
                    $validator->errors()->add('current_password', 'Kata sandi saat ini tidak benar.');
                }
            },
        ];
    }
}
