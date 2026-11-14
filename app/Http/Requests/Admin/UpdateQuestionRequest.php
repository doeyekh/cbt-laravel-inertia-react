<?php

namespace App\Http\Requests\Admin;

use App\Enums\QuestionType;
use App\Http\Requests\Admin\Concerns\ValidatesQuestionAnswers;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateQuestionRequest extends FormRequest
{
    use ValidatesQuestionAnswers;

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
        $questionBank = $this->route('questionBank');
        $question = $this->route('question');
        $type = (string) $this->input('type');

        return [
            'number' => [
                'required',
                'integer',
                'min:1',
                'max:500',
                Rule::unique('questions', 'number')
                    ->where(fn ($query) => $query
                        ->where('question_bank_id', $questionBank?->id)
                        ->where('type', $type))
                    ->ignore($question instanceof Question ? $question : null),
            ],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,mpeg,ogg,m4a', 'max:10240'],
            'remove_image' => ['sometimes', 'boolean'],
            'remove_audio' => ['sometimes', 'boolean'],
            ...$this->questionAnswerRules(),
        ];
    }

    /**
     * Add validation rules that depend on the question bank context.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $questionBank = $this->route('questionBank');

                if (! $questionBank instanceof QuestionBank || $validator->errors()->has('type')) {
                    return;
                }

                $type = QuestionType::tryFrom((string) $this->input('type'));

                if ($type === null) {
                    return;
                }

                $requiredCount = (int) $questionBank->{$type->countColumn()};

                if ($requiredCount === 0) {
                    $validator->errors()->add('type', "Tipe soal {$type->label()} tidak termasuk dalam komposisi bank soal ini.");

                    return;
                }

                if (! $validator->errors()->has('number') && (int) $this->input('number') > $requiredCount) {
                    $validator->errors()->add('number', "Nomor soal maksimal {$requiredCount} untuk tipe {$type->label()}.");
                }

                $this->validateQuestionAnswers($validator, $type, $questionBank);
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
            'number.required' => 'Nomor soal wajib diisi.',
            'number.integer' => 'Nomor soal harus berupa angka.',
            'number.min' => 'Nomor soal minimal 1.',
            'number.max' => 'Nomor soal maksimal 500.',
            'number.unique' => 'Nomor soal sudah digunakan pada tipe soal ini.',
            'type.required' => 'Tipe soal wajib dipilih.',
            'type.enum' => 'Tipe soal tidak valid.',
            'body.required' => 'Teks soal wajib diisi.',
            'body.max' => 'Teks soal maksimal 5000 karakter.',
            'is_active.boolean' => 'Status aktif soal tidak valid.',
            'image.image' => 'Gambar harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 2 MB.',
            'audio.mimes' => 'Audio harus berformat MP3, WAV, OGG, atau M4A.',
            'audio.max' => 'Ukuran audio maksimal 10 MB.',
            'options.*.label.required_with' => 'Label opsi wajib diisi.',
            'options.*.text.required_with' => 'Teks opsi wajib diisi.',
            'options.*.text.max' => 'Teks opsi maksimal 1000 karakter.',
            'options.*.score.numeric' => 'Bobot opsi harus berupa angka.',
            'options.*.image.image' => 'Gambar opsi harus berupa gambar.',
            'options.*.image.mimes' => 'Gambar opsi harus berformat JPG, PNG, atau WEBP.',
            'options.*.image.max' => 'Ukuran gambar opsi maksimal 2 MB.',
            'options.*.audio.mimes' => 'Audio opsi harus berformat MP3, WAV, OGG, atau M4A.',
            'options.*.audio.max' => 'Ukuran audio opsi maksimal 10 MB.',
            'pairs.*.left_text.required_with' => 'Kolom kiri wajib diisi.',
            'pairs.*.right_text.required_with' => 'Kolom kanan wajib diisi.',
            'blanks.*.answers.required_with' => 'Jawaban yang diterima wajib diisi.',
            'blanks.*.answers.*.required' => 'Jawaban yang diterima tidak boleh kosong.',
        ];
    }
}
