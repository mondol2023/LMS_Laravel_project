<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:coupons,code'],
            'discount' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:flat,percent'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'discount_till' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'The coupon code is required.',
            'code.unique' => 'This coupon code is already taken.',
            'discount.required' => 'The discount amount is required.',
            'discount.numeric' => 'The discount must be a number.',
            'discount.min' => 'The discount must be at least 0.',
            'discount_type.required' => 'The discount type is required.',
            'discount_type.in' => 'The discount type must be either flat or percent.',
            'max_uses.integer' => 'Max uses must be a whole number.',
            'max_uses.min' => 'Max uses must be at least 1.',
            'discount_till.date' => 'The expiry date must be a valid date.',
            'discount_till.after' => 'The expiry date must be in the future.',
        ];
    }
}
