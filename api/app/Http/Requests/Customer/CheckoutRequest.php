<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'exists:addresses,id'],
            'payment_method' => ['sometimes', 'in:cod,online'],
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.exists' => 'Please select a valid delivery address.',
        ];
    }
}
