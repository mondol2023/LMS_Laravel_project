<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'package_id' => ['required', 'exists:subscription_packages,id'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required.',
            'user_id.exists' => 'Selected user does not exist.',
            'package_id.required' => 'Package is required.',
            'package_id.exists' => 'Selected package does not exist.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'notes.max' => 'Notes must not exceed 500 characters.',
        ];
    }
}
