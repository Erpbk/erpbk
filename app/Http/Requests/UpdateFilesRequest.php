<?php

namespace App\Http\Requests;

use App\Models\Files;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFilesRequest extends FormRequest
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
        $rules = Files::$rules;
        $rules['file_name'] = 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048';

        return $rules;
    }
}
