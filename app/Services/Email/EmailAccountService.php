<?php

namespace App\Services\Email;

use App\Models\EmailAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class EmailAccountService
{
  /**
   * Active email accounts assigned to the user in the current company.
   */
  public function getActiveAssignedAccounts(User $user): Collection
  {
    if (!$this->actorCanUseCompanyMail($user)) {
      return new Collection();
    }

    return $user->activeEmailAccounts()
      ->orderBy('email')
      ->get();
  }

  public function userCanUseAccount(User $user, EmailAccount $account): bool
  {
    if (!$account->isActive() || !$this->actorCanUseCompanyMail($user)) {
      return false;
    }

    return $user->emailAccounts()
      ->where('email_accounts.id', $account->id)
      ->exists();
  }

  /**
   * @return array{ready: bool, message?: string, status?: int, from_email?: string, from_name?: string, account?: EmailAccount}
   */
  public function prepareAccountSmtp(User $actor, int $emailAccountId): array
  {
    if (!$this->actorCanUseCompanyMail($actor)) {
      return [
        'ready' => false,
        'message' => 'You are not allowed to send email for this company.',
        'status' => 403,
      ];
    }

    $account = EmailAccount::query()->find($emailAccountId);
    if (!$account || !$account->isActive()) {
      return [
        'ready' => false,
        'message' => 'The selected sender email account is not available.',
        'status' => 422,
      ];
    }

    if (!$this->userCanUseAccount($actor, $account)) {
      return [
        'ready' => false,
        'message' => 'You are not assigned to the selected email account.',
        'status' => 403,
      ];
    }

    if (!$this->applySmtpTransport($account, $actor)) {
      return [
        'ready' => false,
        'message' => 'Failed to load SMTP credentials for the selected email account. Re-save the account and try again.',
        'status' => 422,
      ];
    }

    return [
      'ready' => true,
      'from_email' => strtolower(trim((string) $account->email)),
      'from_name' => $this->resolveFromName($account, $actor),
      'account' => $account,
      'status' => 200,
    ];
  }

  public function resolveFromName(EmailAccount $account, User $actor): string
  {
    $displayName = trim((string) ($account->display_name ?? ''));
    if ($displayName !== '') {
      return $displayName;
    }

    $actorName = is_string($actor->name) ? trim($actor->name) : '';
    if ($actorName !== '') {
      return $actorName;
    }

    return (string) ($actor->first_name ?? 'User');
  }

  /**
   * @param  array<int, string>  $ccEmails
   * @return array<int, string>
   */
  public function normalizeCcEmails(array $ccEmails): array
  {
    $normalized = [];

    foreach ($ccEmails as $email) {
      if (!is_string($email)) {
        continue;
      }

      $email = trim($email);
      if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $normalized[] = strtolower($email);
      }
    }

    return array_values(array_unique($normalized));
  }

  private function actorCanUseCompanyMail(User $actor): bool
  {
    return app(UserEmailService::class)->actorCanUseCompanyMail($actor);
  }

  private function applySmtpTransport(EmailAccount $account, User $actor): bool
  {
    $appPassword = $account->getDecryptedAppPassword();
    $appPassword = is_string($appPassword)
      ? UserEmailService::normalizeGmailAppPassword($appPassword)
      : '';

    if ($appPassword === '') {
      return false;
    }

    $smtpUsername = strtolower(trim((string) $account->email));
    if ($smtpUsername === '') {
      return false;
    }

    $host = config('mail.mailers.smtp.host') ?: env('MAIL_HOST', 'smtp.gmail.com');
    $port = (int) (config('mail.mailers.smtp.port') ?: env('MAIL_PORT', 587));
    $encryption = config('mail.mailers.smtp.encryption') ?: env('MAIL_ENCRYPTION', 'tls');

    config([
      'mail.default' => 'smtp',
      'mail.mailers.smtp.host' => $host,
      'mail.mailers.smtp.port' => $port,
      'mail.mailers.smtp.encryption' => $encryption === 'null' ? null : $encryption,
      'mail.mailers.smtp.username' => $smtpUsername,
      'mail.mailers.smtp.password' => $appPassword,
      'mail.from.address' => $smtpUsername,
      'mail.from.name' => $this->resolveFromName($account, $actor),
    ]);

    app('mail.manager')->purge('smtp');
    Mail::purge('smtp');

    return true;
  }
}
