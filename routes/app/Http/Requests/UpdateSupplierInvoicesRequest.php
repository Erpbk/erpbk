<?php

namespace App\Http\Requests;

use App\Models\SupplierInvoices;
use Illuminate\Foundation\Http\FormRequest;


class UpdateSupplierInvoicesRequest extends FormRequest
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
        $rules = SupplierInvoices::$rules;
        if ($this->input('type') === 'order') {
            $rules['garage_id'] = 'nullable|exists:garages,id';
        } else {
            $rules['garage_id'] = 'required|exists:garages,id';
        }

        return $rules;
    }
}
