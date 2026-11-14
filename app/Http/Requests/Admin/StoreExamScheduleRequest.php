<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesExamSchedule;
use App\Models\ExamPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamScheduleRequest extends FormRequest
{
    use ValidatesExamSchedule;

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
        return $this->examScheduleRules();
    }

    /**
     * Add validation rules that depend on the exam package context.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $examPackage = $this->route('examPackage');

                if ($examPackage instanceof ExamPackage) {
                    $this->validateExamSchedule($validator, $examPackage);
                }
            },
        ];
    }
}
