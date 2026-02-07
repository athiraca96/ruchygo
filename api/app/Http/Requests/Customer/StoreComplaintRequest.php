<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
