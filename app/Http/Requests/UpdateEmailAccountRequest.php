<?php

namespace App\Http\Requests;

use App\Models\EmailAccount;
use App\Services\Email\UserEmailService;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailAccountRequest extends FormRequest
{
  public function authorize(): bool
  {
    $account = $this->route('email_account');

    return $account instanceof EmailAccount
      && ($this->user()?->can('update', $account) ?? false);
  }

  public function rules(): array
  {
    $companyId = CompanyContext::id();
    $account = $this->route('email_account');
    $accountId = $account instanceof EmailAccount ? $account->id : null;

    return [
      'email' => [
        'required',
        'email',
        'max:255',
        Rule::unique('email_accounts', 'email')
          ->ignore($accountId)
          ->where(function ($query) use ($companyId) {
            if ($companyId !== null) {
              $query->where('company_id', $companyId);
            }
          }),
      ],
      'app_password' => 'nullable|string|min:16',
      'display_name' => 'nullable|string|max:255',
      'status' => 'required|in:active,inactive',
      'user_ids' => 'nullable|array',
      'user_ids.*' => $this->companyUserExistsRule(),
    ];
  }

  protected function prepareForValidation(): void
  {
    if ($this->filled('app_password')) {
      $this->merge([
        'app_password' => UserEmailService::normalizeGmailAppPassword((string) $this->input('app_password')),
      ]);
    }
  }

  private function companyUserExistsRule(): \Illuminate\Validation\Rules\Exists
  {
    $rule = Rule::exists('users', 'id');
    $companyId = CompanyContext::id();
    if ($companyId !== null) {
      $rule = $rule->where(fn($query) => $query->where('company_id', $companyId));
    }

    return $rule;
  }
}
