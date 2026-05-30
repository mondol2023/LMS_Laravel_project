<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EvaluateWritingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'writing' => ['required', 'array', 'min:1', 'max:4'],
            'writing.*.q' => ['required', 'string'],
            'writing.*.ans' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'writing.required' => 'Writing tasks data is required.',
            'writing.array' => 'Writing tasks must be an array.',
            'writing.min' => 'At least one writing task is required.',
            'writing.*.q.required' => 'Each writing task must have a question.',
            'writing.*.ans.required' => 'Each writing task must have an answer.',
            'writing.*.ans.min' => 'Each answer must be at least 10 characters.',
        ];
    }
}
