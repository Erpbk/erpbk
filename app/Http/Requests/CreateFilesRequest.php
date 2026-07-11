<?php

namespace App\Http\Requests;

use App\Models\Files;
use Illuminate\Foundation\Http\FormRequest;

class CreateFilesRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $type = trim((string) $this->input('type', ''));
        $typeId = $this->input('type_id');

        $this->merge([
            'type' => $type === '' ? null : $type,
            'type_id' => ($typeId === '' || $typeId === null || (int) $typeId < 1) ? null : (int) $typeId,
            'expiry_date' => $this->input('expiry_date') === '' ? null : $this->input('expiry_date'),
            'name' => $this->input('name') === '0' || $this->input('name') === 0
                ? ($this->input('suggested_name') ?: 'document')
                : $this->input('name'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return Files::$rules;
    }
}
