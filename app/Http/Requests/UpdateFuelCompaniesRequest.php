<?php

namespace App\Http\Requests;

use App\Models\FuelCompany;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuelCompaniesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = CompanyContext::id();
        $raw = $this->route('fuelCompany');
        $id = is_object($raw) ? (int) $raw->getKey() : (int) $raw;

        return array_merge(FuelCompany::$rules, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fuel_companies', 'name')
                    ->where(function ($q) use ($companyId) {
                        return $q->where('company_id', $companyId);
                    })
                    ->ignore($id),
            ],
        ]);
    }
}
