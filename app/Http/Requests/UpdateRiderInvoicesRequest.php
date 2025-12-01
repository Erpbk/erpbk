<?php

namespace App\Http\Requests;

use App\Models\RiderInvoices;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderInvoicesRequest extends FormRequest
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

        // Add custom validation for duplicate invoices (excluding current invoice)
        $rules['rider_id'] = function ($attribute, $value, $fail) {
            if ($this->billing_month && $value) {
                $billingMonth = $this->billing_month . '-01';
                $currentInvoiceId = $this->route('riderInvoice'); // Get ID from route

                $existingInvoice = \App\Models\RiderInvoices::where('rider_id', $value)
                    ->where('billing_month', $billingMonth)
                    ->where('id', '!=', $currentInvoiceId) // Exclude current invoice
                    ->first();

                if ($existingInvoice) {
                    $fail('An invoice for this rider has already been generated for the selected billing month.');
                }
            }
        };

        return $rules;
    }
}
