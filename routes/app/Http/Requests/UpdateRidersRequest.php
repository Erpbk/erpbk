<?php

namespace App\Http\Requests;

use App\Models\Riders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRidersRequest extends FormRequest
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
    $rules = Riders::$rules;
    $rules['rider_id'] = ['required', Rule::unique('riders')->ignore($this->rider)];
    return $rules;
  }
}
