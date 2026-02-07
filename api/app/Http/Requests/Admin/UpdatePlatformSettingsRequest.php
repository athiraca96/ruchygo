<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform_fee_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'shipping_fee' => ['sometimes', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['sometimes', 'numeric', 'min:0'],
            'return_window_days' => ['sometimes', 'integer', 'min:0'],
            'replacement_window_days' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
