<?php

namespace App\Http\Requests;

use App\Models\RiderInvoices;
use Illuminate\Foundation\Http\FormRequest;

class CreateRiderInvoicesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = RiderInvoices::$rules;

        // Add custom validation for duplicate invoices
        $rules['rider_id'] = function ($attribute, $value, $fail) {
            if ($this->billing_month && $value) {
                $billingMonth = $this->billing_month . '-01';
                $existingInvoice = \App\Models\RiderInvoices::where('rider_id', $value)
                    ->where('billing_month', $billingMonth)
                    ->first();

                if ($existingInvoice) {
                    $fail('An invoice for this rider has already been generated for the selected billing month.');
                }
            }
        };

        return $rules;
    }
}
