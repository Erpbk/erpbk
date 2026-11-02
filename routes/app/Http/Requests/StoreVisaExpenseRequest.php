<?php

namespace App\Http\Requests;

use App\Models\visa_expenses;
use Illuminate\Foundation\Http\FormRequest;

class StoreVisaExpenseRequest extends FormRequest
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
         return visa_expenses::$rules;
    }
}
