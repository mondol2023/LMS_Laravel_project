<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $examId = $this->route('exam');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'user_exam_id' => ['required', 'string', 'max:255', Rule::unique('exams', 'user_exam_id')->ignore($examId)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The exam name is required.',
            'name.max' => 'The exam name must not exceed 255 characters.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'user_exam_id.required' => 'The user exam ID is required.',
            'user_exam_id.max' => 'The user exam ID must not exceed 255 characters.',
            'user_exam_id.unique' => 'This user exam ID is already taken.',
        ];
    }
}
