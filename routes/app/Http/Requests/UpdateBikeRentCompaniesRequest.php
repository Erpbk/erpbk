<?php

namespace App\Http\Requests;

use App\Models\BikeRentCompany;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBikeRentCompaniesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = CompanyContext::id();
        $raw = $this->route('bikeRentCompany');
        $id = is_object($raw) ? (int) $raw->getKey() : (int) $raw;

        return array_merge(BikeRentCompany::$rules, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bike_rent_companies', 'name')
                    ->where(function ($q) use ($companyId) {
                        return $q->where('company_id', $companyId);
                    })
                    ->ignore($id),
            ],
        ]);
    }
}
