<?php

namespace App\Http\Requests;

use App\Models\Banks;
use App\Support\ModuleFieldSource;
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

        if (!Schema::hasTable('banks')) {
            return $rules;
        }

        $requiredFieldKeys = ModuleFieldSource::schemaFieldKeysForModule('cash_banks');

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
