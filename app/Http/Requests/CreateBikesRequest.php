<?php

namespace App\Http\Requests;

use App\Models\Bikes;
use App\Models\BikeCustomField;
use App\Models\LeasingCompanies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

class CreateBikesRequest extends FormRequest
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
        $rules = Bikes::$rules;

        $bikeColumns = array_flip(Schema::getColumnListing('bikes'));

        if (!isset($bikeColumns['leased_return_company_id'])) {
            unset($rules['leased_return_company_id']);
        }

        $normalizePresenceRule = function ($rule, bool $required) {
            if (is_array($rule)) {
                $tokens = array_values(array_filter($rule, function ($item) {
                    return !is_string($item) || ($item !== 'required' && $item !== 'nullable');
                }));
                array_unshift($tokens, $required ? 'required' : 'nullable');
                return $tokens;
            }

            $tokens = array_values(array_filter(explode('|', (string) $rule), function ($item) {
                return $item !== '' && $item !== 'required' && $item !== 'nullable';
            }));

            array_unshift($tokens, $required ? 'required' : 'nullable');
            return implode('|', $tokens);
        };

        $fixedKeys = BikeCustomField::allFixedFieldKeys();

        foreach ($fixedKeys as $fieldKey) {
            if (!isset($bikeColumns[$fieldKey])) {
                continue;
            }

            // Required comes only from Role Field Permissions (not Module Settings / hardcoded specs).
            $isRequired = \App\Support\RoleFieldAccess::isRequired('bike', $fieldKey)
                && \App\Support\RoleFieldAccess::canEdit('bike', $fieldKey)
                && \App\Support\RoleFieldAccess::canView('bike', $fieldKey);

            $baseRule = $rules[$fieldKey] ?? 'nullable';
            $rules[$fieldKey] = $normalizePresenceRule($baseRule, $isRequired);
        }

        BikeCustomField::query()
            ->get(['id'])
            ->each(function ($field) use (&$rules) {
                $cfName = 'cf_' . $field->id;
                if (\App\Support\RoleFieldAccess::isRequired('bike', $cfName)
                    && \App\Support\RoleFieldAccess::canEdit('bike', $cfName)
                    && \App\Support\RoleFieldAccess::canView('bike', $cfName)) {
                    $rules['custom_field_values.' . $field->id] = 'required';
                }
            });

        // Bike-specific: cyclist vehicle type disables some required fields
        $vehicleTypeId = $this->input('vehicle_type');
        $vehicleModel = \App\Support\CompanyQuery::table('vehicle_models')->find($vehicleTypeId);
        if ($vehicleModel && strtolower((string) $vehicleModel->name) === 'cyclist') {
            foreach (['bike_code', 'chassis_number', 'engine', 'model_type', 'policy_no'] as $key) {
                if (isset($rules[$key])) {
                    $rules[$key] = $normalizePresenceRule($rules[$key], false);
                }
            }
        }

        // bike_owner is derived from company select; never required from the form.
        $rules['bike_owner'] = 'nullable|string|in:Owned,Leased';

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $company = $this->input('company');
            if ($company === null || $company === '') {
                return;
            }
            if ($company === 'own') {
                return;
            }
            if (! LeasingCompanies::whereKey($company)->exists()) {
                $validator->errors()->add('company', 'Selected company is invalid.');
            }
        });
    }
}
