<?php

namespace App\Http\Requests;

use App\Models\EmailAccount;
use App\Models\User;
use App\Services\Email\EmailAccountService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SendRiderEmailRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user() instanceof User;
  }

  public function rules(): array
  {
    return [
      'email_account_id' => [
        'required',
        'integer',
        Rule::exists('email_accounts', 'id')->where(fn($query) => $query->where('status', EmailAccount::STATUS_ACTIVE)),
      ],
      'email_to' => 'required|email',
      'email_subject' => 'required|string|max:255',
      'email_message' => 'required|string',
      'email_heading' => 'nullable|string|max:255',
      'email_cc' => 'nullable|array',
      'email_cc.*' => 'email|distinct',
      'month' => 'nullable|date_format:Y-m',
    ];
  }

  public function withValidator(Validator $validator): void
  {
    $validator->after(function (Validator $validator): void {
      $user = $this->user();
      if (!$user instanceof User) {
        return;
      }

      $accountId = (int) $this->input('email_account_id');
      if ($accountId <= 0) {
        return;
      }

      $account = EmailAccount::query()->find($accountId);
      if (!$account || !app(EmailAccountService::class)->userCanUseAccount($user, $account)) {
        $validator->errors()->add('email_account_id', 'You are not assigned to the selected email account.');
      }
    });
  }
}
