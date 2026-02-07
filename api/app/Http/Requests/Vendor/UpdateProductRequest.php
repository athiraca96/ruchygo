<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        // Validate discount_price < price if both are present
        if ($this->has('price') && $this->has('discount_price') && $this->discount_price !== null) {
            $rules['discount_price'][] = 'lt:price';
        }

        return $rules;
    }
}
