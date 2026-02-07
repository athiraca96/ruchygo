<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'otp' => ['required', 'string', 'size:6'],
            'role' => ['sometimes', 'in:vendor,customer'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number (10-15 digits).',
            'otp.size' => 'OTP must be 6 digits.',
        ];
    }
}
