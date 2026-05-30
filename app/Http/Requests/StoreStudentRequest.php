<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'roll_number' => ['required', 'string', 'max:255', 'unique:users,roll_number'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'passport' => ['nullable', 'string', 'max:50'],
            'institute' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:1000'],
            'create_another' => ['nullable', 'boolean'],
            'package_id' => ['nullable', 'integer', 'exists:subscription_packages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The student name is required.',
            'name.max' => 'The name must not exceed 255 characters.',
            'roll_number.required' => 'The roll number is required.',
            'roll_number.unique' => 'This roll number is already taken.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'phone.unique' => 'This phone number is already registered.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 6 characters.',
            'passport.max' => 'The passport number must not exceed 50 characters.',
            'institute.max' => 'The institute name must not exceed 255 characters.',
            'reference.max' => 'The reference must not exceed 1000 characters.',
        ];
    }
}
