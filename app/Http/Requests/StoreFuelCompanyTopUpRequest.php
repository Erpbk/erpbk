<?php

namespace App\Http\Requests;

use App\Rules\ActiveBank;
use Illuminate\Foundation\Http\FormRequest;

class StoreFuelCompanyTopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return user_can('fuel_cards_companies_create') || user_can('fuel_cards_companies_edit');
    }

    public function rules(): array
    {
        return [
            'fuel_company_id' => 'required|numeric|exists:fuel_companies,id',
            'bank_id' => ['required', 'numeric', 'exists:banks,id', new ActiveBank],
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'date_of_payment' => 'required|date',
            'billing_month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'fuel_company_id.required' => 'Please select a fuel company.',
            'bank_id.required' => 'Please select a bank account to credit.',
            'amount_type.required' => 'Payment mode is required.',
            'date_of_payment.required' => 'Payment date is required.',
            'billing_month.required' => 'Billing month is required.',
            'amount.required' => 'Top-up amount is required.',
            'amount.min' => 'Top-up amount must be greater than zero.',
            'description.required' => 'Narration is required.',
        ];
    }
}
