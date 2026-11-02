<?php

namespace App\Http\Requests;

use App\Models\RiderAttendance;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderAttendanceRequest extends FormRequest
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
        $rules = RiderAttendance::$rules;
        
        return $rules;
    }
}
