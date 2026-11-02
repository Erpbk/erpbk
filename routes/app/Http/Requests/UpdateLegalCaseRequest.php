<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLegalCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:legal_cases,id',
            'case_status' => 'required|string|max:255',
            'billing_month' => 'required|date_format:Y-m',
            'date' => 'required|date',
            'detail' => 'nullable|string|max:500',
            'reference_number' => 'required|string|max:255',
        ];
    }
}
