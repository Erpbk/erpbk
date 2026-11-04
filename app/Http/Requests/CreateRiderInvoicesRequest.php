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

        // Duplicate = same rider + billing month + project
        $rules['rider_id'] = function ($attribute, $value, $fail) {
            if ($this->billing_month && $value && $this->customer_id) {
                $billingMonth = $this->billing_month.'-01';
                $existingInvoice = RiderInvoices::where('rider_id', $value)
                    ->where('billing_month', $billingMonth)
                    ->where('customer_id', $this->customer_id)
                    ->first();

                if ($existingInvoice) {
                    $fail('An invoice for this rider has already been generated for the selected billing month and project.');
                }
            }
        };

        return $rules;
    }
}
