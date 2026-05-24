<?php

namespace App\Http\Requests;

use App\Models\Sims;
use App\Support\ModuleFieldSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSimsRequest extends FormRequest
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
        $baseRules = Sims::$rules;
        unset($baseRules['number']);

        return ModuleFieldSettings::validationRulesForModule('sims', $baseRules, [
            'fields' => ['company', 'vendor', 'fleet_supervisor', 'emi', 'branch_id'],
        ]);
    }
}
