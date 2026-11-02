<?php

namespace App\Http\Requests;

use App\Models\Sims;
use App\Support\ModuleFieldSettings;
use Illuminate\Foundation\Http\FormRequest;

class CreateSimsRequest extends FormRequest
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
        return ModuleFieldSettings::validationRulesForModule('sims', Sims::$rules);
    }
}
