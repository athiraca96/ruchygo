<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'order_item_id' => ['required', 'exists:order_items,id'],
            'type' => ['required', 'in:return,replacement'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
