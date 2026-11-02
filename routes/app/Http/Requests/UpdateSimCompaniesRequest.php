<?php

namespace App\Http\Requests;

use App\Models\SimCompany;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSimCompaniesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = CompanyContext::id();
        $raw = $this->route('simCompany');
        $id = is_object($raw) ? (int) $raw->getKey() : (int) $raw;

        return array_merge(SimCompany::$rules, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sim_companies', 'name')
                    ->where(function ($q) use ($companyId) {
                        return $q->where('company_id', $companyId);
                    })
                    ->ignore($id),
            ],
        ]);
    }
}
