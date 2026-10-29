<?php

namespace App\Http\Requests\VoucherApproval;

use Illuminate\Foundation\Http\FormRequest;

class RejectVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A rejection reason is required.',
            'reason.min' => 'Rejection reason must be at least 10 characters.',
        ];
    }
}
