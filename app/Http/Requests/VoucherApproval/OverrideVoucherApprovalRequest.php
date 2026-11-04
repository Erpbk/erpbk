<?php

namespace App\Http\Requests\VoucherApproval;

use Illuminate\Foundation\Http\FormRequest;

class OverrideVoucherApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remark' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'remark.required' => 'A remark is required for this override action.',
            'remark.min' => 'Remark must be at least 10 characters.',
        ];
    }
}
