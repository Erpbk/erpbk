<?php

namespace App\Http\Requests;

use App\Models\BikeRentCompany;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBikeRentCompaniesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = CompanyContext::id();

        return array_merge(BikeRentCompany::$rules, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bike_rent_companies', 'name')->where(function ($q) use ($companyId) {
                    return $q->where('company_id', $companyId);
                }),
            ],
        ]);
    }
}
