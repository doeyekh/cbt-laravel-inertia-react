<?php

namespace App\Http\Requests\Admin\School;

use App\Enums\UserRole;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', School::class) ?? false;
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
                'max:150',
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('schools'),
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'headmaster_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', UserRole::Guru->value)
                    ->whereNull('deleted_at'),
            ],
            'admin_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', UserRole::Guru->value)
                    ->whereNull('deleted_at'),
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
            'name.required' => 'Nama sekolah wajib diisi.',
            'name.max' => 'Nama sekolah maksimal 150 karakter.',
            'code.required' => 'Kode sekolah wajib diisi.',
            'code.max' => 'Kode sekolah maksimal 20 karakter.',
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode sekolah sudah terdaftar.',
            'address.max' => 'Alamat maksimal 255 karakter.',
            'headmaster_id.exists' => 'Kepala sekolah harus dipilih dari pengguna dengan role Guru.',
            'admin_id.exists' => 'Admin sekolah harus dipilih dari pengguna dengan role Guru.',
        ];
    }
}
