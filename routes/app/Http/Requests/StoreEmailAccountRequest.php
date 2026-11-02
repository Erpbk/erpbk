<?php

namespace App\Http\Requests;

use App\Models\EmailAccount;
use App\Services\Email\UserEmailService;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailAccountRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()?->can('create', EmailAccount::class) ?? false;
  }

  public function rules(): array
  {
    $companyId = CompanyContext::id();

    return [
      'email' => [
        'required',
        'email',
        'max:255',
        Rule::unique('email_accounts', 'email')->where(function ($query) use ($companyId) {
          if ($companyId !== null) {
            $query->where('company_id', $companyId);
          }
        }),
      ],
      'app_password' => 'required|string|min:16',
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
