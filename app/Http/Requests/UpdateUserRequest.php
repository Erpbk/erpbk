<?php

namespace App\Http\Requests;

use App\Helpers\IConstants;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
    $userId = $this->route('user') ?? $this->route('id');
    $user = User::find($userId);

    if ($user && $user->hasRole(IConstants::ROLE_SUPER_ADMIN)) {
      return [
        'password' => 'nullable|string|min:6|confirmed',
      ];
    }

    $rules = User::$rules;
    $userId = $this->route('user') ?? $this->route('id');

    // Allow keeping the current email/username when updating
    $rules['email'] = ['nullable', 'string', 'max:255', 'email', Rule::unique('users')->ignore($userId)];
    $rules['username'] = ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($userId)];

    // Password is optional on update
    $rules['password'] = 'nullable|string|min:6|confirmed';
    $rules['email_account_ids'] = 'nullable|array';
    $rules['email_account_ids.*'] = 'integer|distinct|exists:email_accounts,id';
    return $rules;
  }
}
