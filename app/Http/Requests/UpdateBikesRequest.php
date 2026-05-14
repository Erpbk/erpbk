<?php

namespace App\Http\Requests;

use App\Models\Bikes;
use App\Models\BikeCustomField;
use App\Models\BikeFieldCategoryAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class UpdateBikesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $v = $this->input('leased_return_company_id');
        if ($v === '' || $v === null) {
            $this->merge(['leased_return_company_id' => null]);
        }
    }

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

        $assignmentTable = (new BikeFieldCategoryAssignment())->getTable();

        $hasRequiredColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_required');
        $hasVisibleColumn = Schema::hasTable($assignmentTable) && Schema::hasColumn($assignmentTable, 'is_visible');

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

        $assignmentColumns = ['field_key'];
        if ($hasRequiredColumn) {
            $assignmentColumns[] = 'is_required';
        }
        if ($hasVisibleColumn) {
            $assignmentColumns[] = 'is_visible';
        }

        $assignments = BikeFieldCategoryAssignment::query()
            ->get($assignmentColumns)
            ->keyBy('field_key');

        $fixedKeys = BikeCustomField::allFixedFieldKeys();

        foreach ($fixedKeys as $fieldKey) {
            if (!isset($bikeColumns[$fieldKey])) {
                continue;
            }

            $assignment = $assignments->get($fieldKey);
            $isVisible = !$hasVisibleColumn || !$assignment || $assignment->is_visible === null
                ? true
                : (bool) $assignment->is_visible;

            $isRequired = ($assignment && $hasRequiredColumn)
                ? (bool) $assignment->is_required
                : false;

            $baseRule = $rules[$fieldKey] ?? 'nullable';
            $rules[$fieldKey] = $normalizePresenceRule($baseRule, $isVisible && $isRequired);
        }

        BikeCustomField::query()
            ->where('is_mandatory', 1)
            ->get(['id'])
            ->each(function ($field) use (&$rules) {
                $rules['custom_field_values.' . $field->id] = 'required';
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

        return $rules;
    }
}
