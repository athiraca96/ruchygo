<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:open,in_progress,resolved,closed'],
            'admin_response' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
