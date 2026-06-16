<?php

namespace App\Http\Requests;

use App\Models\license_expenses;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenseExpenseRequest extends FormRequest
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
         $rules = license_expenses::$rules;
        
        return $rules;
    }
}
