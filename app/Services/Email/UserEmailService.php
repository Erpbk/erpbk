<?php

namespace App\Services\Email;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserEmailService
{
  /**
   * Gmail app passwords are 16 characters; users often paste them with spaces.
   */
  public static function normalizeGmailAppPassword(string $password): string
  {
    return preg_replace('/\s+/', '', trim($password)) ?? '';
  }

  /**
   * User-facing hint when Gmail returns 535 / BadCredentials.
   */
  public function formatMailFailureMessage(\Throwable $e): string
  {
    $raw = $e->getMessage();

    if (
      str_contains($raw, '535')
      || str_contains($raw, 'BadCredentials')
      || str_contains($raw, 'Username and Password not accepted')
    ) {
      return 'Gmail rejected the App Password for this account. In Google Account → Security → 2-Step Verification → App passwords, create a new Mail app password (16 characters). Paste it in Settings → Email Settings with no spaces. The Gmail address must match the account that created the password (e.g. absdevelops.17@gmail.com). Do not use your normal Gmail login password.';
    }

    if (config('app.debug')) {
      return 'Failed to send email: ' . trim($raw);
    }

    return 'Failed to send email. Check Email Settings and Gmail app password, then try again.';
  }

  /**
   * Resolve a valid email address for the user.
   */
  public function resolveUserEmail(User $user): ?string
  {
    $email = is_string($user->email) ? trim($user->email) : '';
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return $email;
    }

    $usernameAsEmail = is_string($user->username) ? trim($user->username) : '';
    if ($usernameAsEmail !== '' && filter_var($usernameAsEmail, FILTER_VALIDATE_EMAIL)) {
      return $usernameAsEmail;
    }

    return null;
  }

  /**
   * Company contact email (companies.email), when set.
   */
  public function resolveCompanyContactEmail(?int $companyId = null): ?string
  {
    $companyId = $companyId ?? CompanyContext::id();
    if ($companyId === null) {
      return null;
    }

    $company = Company::query()->find($companyId);
    $email = is_string($company?->email) ? trim($company->email) : '';
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return $email;
    }

    return null;
  }

  /**
   * Whether the acting user may send mail in the current company context.
   */
  public function actorCanUseCompanyMail(User $actor): bool
  {
    $companyId = CompanyContext::id();
    if ($companyId === null) {
      return true;
    }

    if (!empty($actor->company_id)) {
      return (int) $actor->company_id === (int) $companyId;
    }

    // On /app/{company_slug} routes, tenant middleware already validated access.
    $routeCompany = request()?->attributes->get('company');
    if ($routeCompany && isset($routeCompany->id)) {
      return (int) $routeCompany->id === (int) $companyId;
    }

    return false;
  }

  /**
   * SMTP row for the actor, or any configured SMTP for users in the same company.
   */
  public function resolveSmtpSettingsForActor(User $actor): ?object
  {
    if (!$this->actorCanUseCompanyMail($actor)) {
      return null;
    }

    $own = DB::table('user_email_settings')->where('user_id', $actor->id)->first();
    if ($this->settingHasPassword($own)) {
      return $own;
    }

    $companyId = (int) ($actor->company_id ?: CompanyContext::id());
    if ($companyId <= 0) {
      return null;
    }

    return DB::table('user_email_settings as ues')
      ->join('users as u', 'u.id', '=', 'ues.user_id')
      ->where('u.company_id', $companyId)
      ->whereNotNull('ues.smtp_app_password_encrypted')
      ->where('ues.smtp_app_password_encrypted', '!=', '')
      ->orderByDesc('ues.updated_at')
      ->select('ues.*')
      ->first();
  }

  /**
   * Gmail SMTP auth address must match the account that owns the app password.
   */
  public function resolveSmtpAuthEmail(?object $setting, User $actor): ?string
  {
    if ($setting && !empty($setting->user_id)) {
      $owner = User::query()->find((int) $setting->user_id);
      if ($owner) {
        $ownerEmail = $this->resolveUserEmail($owner);
        if ($ownerEmail) {
          return $ownerEmail;
        }
      }
    }

    $actorEmail = $this->resolveUserEmail($actor);
    if ($actorEmail) {
      return $actorEmail;
    }

    return $this->resolveCompanyContactEmail();
  }

  public function hasSmtpConfigured(User $actor): bool
  {
    return $this->resolveSmtpSettingsForActor($actor) !== null;
  }

  /**
   * @return array{ready: bool, message?: string, status?: int, from_email?: string, from_name?: string}
   */
  public function prepareCompanySmtp(User $actor, ?string $preferredSmtpUsername = null): array
  {
    if (!$this->actorCanUseCompanyMail($actor)) {
      return [
        'ready' => false,
        'message' => 'You are not allowed to send email for this company.',
        'status' => 403,
      ];
    }

    $setting = $this->resolveSmtpSettingsForActor($actor);
    if (!$this->settingHasPassword($setting)) {
      return [
        'ready' => false,
        'message' => 'SMTP is not configured for this company. An administrator must create an email account under Settings → Email Accounts and assign it to users.',
        'status' => 422,
      ];
    }

    $smtpUsername = $preferredSmtpUsername ?: $this->resolveSmtpAuthEmail($setting, $actor);
    if (!$smtpUsername) {
      return [
        'ready' => false,
        'message' => 'A valid Gmail address is required for SMTP. Set the user email (who saved Email Settings) or company email in Settings.',
        'status' => 422,
      ];
    }

    if (!$this->applySmtpTransport($setting, $smtpUsername, $actor)) {
      return [
        'ready' => false,
        'message' => 'Failed to load this company\'s SMTP credentials. Re-save Email Settings and try again.',
        'status' => 422,
      ];
    }

    $fromName = is_string($actor->name) && trim($actor->name) !== ''
      ? trim($actor->name)
      : (string) ($actor->first_name ?? 'User');

    return [
      'ready' => true,
      'from_email' => (string) config('mail.from.address'),
      'from_name' => $fromName,
      'status' => 200,
    ];
  }

  /**
   * @deprecated Use prepareCompanySmtp(); kept for backwards compatibility.
   */
  public function configureSmtpForUser(User $user, ?string $smtpUsername = null): bool
  {
    $setting = $this->resolveSmtpSettingsForActor($user);
    $smtpUsername = $smtpUsername ?: $this->resolveSmtpAuthEmail($setting, $user);

    return $this->applySmtpTransport($setting, $smtpUsername, $user);
  }

  public function getCcRecipientEmails(User $actor): array
  {
    if (!$this->actorCanUseCompanyMail($actor)) {
      return [];
    }

    $query = DB::table('user_email_cc_recipients')
      ->where('user_email_cc_recipients.user_id', $actor->id)
      ->join('users as u', 'u.id', '=', 'user_email_cc_recipients.recipient_user_id');

    $companyId = CompanyContext::id();
    if ($companyId !== null) {
      $query->where('u.company_id', $companyId);
    }

    $recipients = $query->get(['u.email', 'u.username']);

    $recipientEmails = array_filter(array_map(function ($row) {
      $email = is_string($row->email) ? trim($row->email) : '';
      if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
      }

      $usernameAsEmail = is_string($row->username) ? trim($row->username) : '';
      return ($usernameAsEmail !== '' && filter_var($usernameAsEmail, FILTER_VALIDATE_EMAIL)) ? $usernameAsEmail : null;
    }, $recipients->all()));

    return array_values(array_unique($recipientEmails));
  }

  private function settingHasPassword(?object $setting): bool
  {
    if (!$setting) {
      return false;
    }

    $encrypted = $setting->smtp_app_password_encrypted ?? null;

    return is_string($encrypted) && trim($encrypted) !== '';
  }

  private function applySmtpTransport(?object $setting, ?string $smtpUsername, User $actor): bool
  {
    if (!$this->settingHasPassword($setting) || !$smtpUsername) {
      return false;
    }

    try {
      $appPassword = Crypt::decryptString($setting->smtp_app_password_encrypted);
    } catch (\Throwable $e) {
      return false;
    }

    $appPassword = is_string($appPassword) ? self::normalizeGmailAppPassword($appPassword) : '';
    if ($appPassword === '') {
      return false;
    }

    $smtpUsername = strtolower(trim($smtpUsername));

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
      'mail.from.name' => is_string($actor->name) && trim($actor->name) !== ''
        ? trim($actor->name)
        : (string) ($actor->first_name ?? 'User'),
    ]);

    app('mail.manager')->purge('smtp');
    Mail::purge('smtp');

    return true;
  }
}
