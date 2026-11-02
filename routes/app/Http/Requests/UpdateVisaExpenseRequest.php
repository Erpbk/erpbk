<?php

namespace App\Http\Requests;

use App\Models\visa_expenses;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVisaExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
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
         $rules = visa_expenses::$rules;
        
        return $rules;
    }
}
