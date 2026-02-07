<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'business_address' => ['required', 'string', 'max:1000'],
            'gst_number' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_ifsc_code' => ['required', 'string', 'max:20'],
            'id_proof_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_proof_document.max' => 'ID proof document must not exceed 5MB.',
        ];
    }
}
