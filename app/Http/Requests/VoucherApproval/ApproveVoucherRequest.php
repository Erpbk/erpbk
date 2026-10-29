<?php

namespace App\Http\Requests\VoucherApproval;

use Illuminate\Foundation\Http\FormRequest;

class ApproveVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy enforced in controller
    }

    public function rules(): array
    {
        return [
            'remark' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
