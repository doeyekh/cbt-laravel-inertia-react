<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
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
            'school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('rooms')
                    ->where(fn ($query) => $query->where('school_id', $this->input('school_id'))),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'building' => [
                'required',
                'string',
                'max:100',
            ],
            'floor' => [
                'required',
                'string',
                'max:20',
            ],
            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],
            'is_active' => ['boolean'],
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
            'code.required' => 'Kode ruangan wajib diisi.',
            'code.max' => 'Kode maksimal 20 karakter.',
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode ruangan sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama ruangan wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'building.required' => 'Gedung wajib diisi.',
            'building.max' => 'Gedung maksimal 100 karakter.',
            'floor.required' => 'Lantai wajib diisi.',
            'floor.max' => 'Lantai maksimal 20 karakter.',
            'capacity.required' => 'Kapasitas wajib diisi.',
            'capacity.integer' => 'Kapasitas harus berupa angka.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'is_active.boolean' => 'Status aktif tidak valid.',
        ];
    }
}
