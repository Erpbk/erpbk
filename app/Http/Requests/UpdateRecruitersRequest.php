<?php

namespace App\Http\Requests;

use App\Models\Recruiters;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecruitersRequest extends FormRequest
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
        $rules = Recruiters::$rules;

        return $rules;
    }
}
