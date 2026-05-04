<?php

namespace App\Http\Requests;

use App\Models\SimCompany;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSimCompaniesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = CompanyContext::id();

        return array_merge(SimCompany::$rules, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sim_companies', 'name')->where(function ($q) use ($companyId) {
                    return $q->where('company_id', $companyId);
                }),
            ],
        ]);
    }
}
