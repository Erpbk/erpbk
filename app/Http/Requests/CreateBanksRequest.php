<?php

namespace App\Http\Requests;

use App\Models\Banks;
use App\Models\ModuleFieldCategoryAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class CreateBanksRequest extends FormRequest
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
        $rules = Banks::$rules;

        if (!Schema::hasTable('module_field_category_assignments')) {
            return $rules;
        }

        $requiredFieldKeys = ModuleFieldCategoryAssignment::query()
            ->where('module_key', 'cash_banks')
            ->whereNotNull('category_id')
            ->where('is_visible', true)
            ->where('is_required', true)
            ->pluck('field_key');

        foreach ($requiredFieldKeys as $fieldKey) {
            if (array_key_exists($fieldKey, $rules)) {
                $existing = (string) $rules[$fieldKey];
                if (!str_contains($existing, 'required')) {
                    $rules[$fieldKey] = 'required|' . ltrim($existing, '|');
                }
            } else {
                $rules[$fieldKey] = 'required';
            }
        }

        return $rules;
    }
}
