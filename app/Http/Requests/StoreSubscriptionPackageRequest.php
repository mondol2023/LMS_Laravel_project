<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Set discount_type to null if discount is not set
        if (empty($this->discount)) {
            $this->merge([
                'discount' => null,
                'discount_type' => null,
                'discount_till' => null,
            ]);
        }

        // Set limits to null if not enabled
        if (! $this->boolean('full_mock_test_enabled')) {
            $this->merge(['full_mock_test_limit' => null]);
        }
        if (! $this->boolean('partial_reading_enabled')) {
            $this->merge(['partial_reading_limit' => null]);
        }
        if (! $this->boolean('partial_writing_enabled')) {
            $this->merge(['partial_writing_limit' => null]);
        }
        if (! $this->boolean('partial_listening_enabled')) {
            $this->merge(['partial_listening_limit' => null]);
        }
        if (! $this->boolean('partial_speaking_enabled')) {
            $this->merge(['partial_speaking_limit' => null]);
        }
        if (! $this->boolean('practice_writing_enabled')) {
            $this->merge(['practice_writing_limit' => null]);
        }
        if (! $this->boolean('practice_speaking_enabled')) {
            $this->merge(['practice_speaking_limit' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:subscription_packages,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'required_with:discount', 'in:flat,percent'],
            'discount_till' => ['nullable', 'date', 'after:now'],

            // Mock Test Limits
            'full_mock_test_enabled' => ['boolean'],
            'full_mock_test_limit' => ['nullable', 'integer', 'min:1'],
            'partial_reading_enabled' => ['boolean'],
            'partial_reading_limit' => ['nullable', 'integer', 'min:1'],
            'partial_writing_enabled' => ['boolean'],
            'partial_writing_limit' => ['nullable', 'integer', 'min:1'],
            'partial_listening_enabled' => ['boolean'],
            'partial_listening_limit' => ['nullable', 'integer', 'min:1'],
            'partial_speaking_enabled' => ['boolean'],
            'partial_speaking_limit' => ['nullable', 'integer', 'min:1'],

            // Practice Modules
            'practice_reading_enabled' => ['boolean'],
            'practice_listening_enabled' => ['boolean'],
            'practice_writing_enabled' => ['boolean'],
            'practice_writing_limit' => ['nullable', 'integer', 'min:1'],
            'practice_speaking_enabled' => ['boolean'],
            'practice_speaking_limit' => ['nullable', 'integer', 'min:1'],

            // Status
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Package name is required.',
            'name.unique' => 'This package name already exists.',
            'duration.required' => 'Duration is required.',
            'duration.integer' => 'Duration must be a number.',
            'duration.min' => 'Duration must be at least 1 month.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price must be at least 0.',
            'discount.min' => 'Discount must be at least 0.',
            'discount_type.required_with' => 'Discount type is required when discount is set.',
            'discount_type.in' => 'Discount type must be either flat or percent.',
            'discount_till.after' => 'Discount valid till must be a future date.',
        ];
    }
}
