<?php

namespace App\Http\Requests;

use App\Models\EmployeeInvoices;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeInvoicesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = EmployeeInvoices::$rules;

        $rules['employee_id'] = function ($attribute, $value, $fail) {
            if ($this->billing_month && $value) {
                $billingMonth = $this->billing_month . '-01';
                $currentId = $this->route('employeeInvoice');

                $exists = EmployeeInvoices::where('employee_id', $value)
                    ->where('billing_month', $billingMonth)
                    ->where('id', '!=', $currentId)
                    ->first();

                if ($exists) {
                    $fail('An invoice for this employee has already been generated for the selected billing month.');
                }
            }
        };

        return $rules;
    }
}

