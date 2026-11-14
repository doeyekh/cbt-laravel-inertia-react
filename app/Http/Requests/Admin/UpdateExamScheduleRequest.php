<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesExamSchedule;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateExamScheduleRequest extends FormRequest
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
                $examSchedule = $this->route('examSchedule');

                if ($examPackage instanceof ExamPackage) {
                    $this->validateExamSchedule(
                        $validator,
                        $examPackage,
                        $examSchedule instanceof ExamSchedule ? $examSchedule : null,
                    );
                }
            },
        ];
    }
}
