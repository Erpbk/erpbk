<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegalCaseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'rider_id' => 'required|exists:legal_case_accounts,id',
            'case_status' => [
                'required',
                'string',
                'max:255',
                Rule::unique('legal_cases')->where(function ($query) {
                    return $query->where('legal_case_account_id', $this->rider_id)->whereNull('deleted_at');
                }),
            ],
            'billing_month' => 'required|date_format:Y-m',
            'date' => 'required|date',
            'detail' => 'nullable|string|max:500',
            'reference_number' => 'required|string|max:255',
        ];
    }
}
