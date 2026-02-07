<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:pending,approved,rejected,completed'],
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
