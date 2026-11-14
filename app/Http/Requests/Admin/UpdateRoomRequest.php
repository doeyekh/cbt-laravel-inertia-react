<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
        $room = $this->route('room');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('rooms')
                    ->where(fn ($query) => $query->where('school_id', $room?->school_id))
                    ->ignore($room),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'building' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'floor' => [
                'sometimes',
                'required',
                'string',
                'max:20',
            ],
            'capacity' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],
            'is_active' => ['sometimes', 'boolean'],
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
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode ruangan sudah terdaftar di sekolah ini.',
            'name.required' => 'Nama ruangan wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'building.required' => 'Gedung wajib diisi.',
            'capacity.integer' => 'Kapasitas harus berupa angka.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'is_active.boolean' => 'Status aktif tidak valid.',
        ];
    }
}
