<?php

namespace App\Http\Requests;

use App\Models\FuelCompany;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFuelCompaniesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = CompanyContext::id();

        return array_merge(FuelCompany::$rules, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fuel_companies', 'name')->where(function ($q) use ($companyId) {
                    return $q->where('company_id', $companyId);
                }),
            ],
        ]);
    }
}
