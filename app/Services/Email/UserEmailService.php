<?php

namespace App\Services\Email;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserEmailService
{
  /**
   * Resolve a valid email address for the user.
   * In this app, login can be based on either `users.email` or `users.username`.
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
   * Configure Laravel SMTP transport for this user using their stored Gmail app password.
   *
   * @return bool True if the SMTP was configured (app password present), false otherwise.
   */
  public function configureSmtpForUser(User $user, ?string $smtpUsername = null): bool
  {
    $setting = \App\Support\CompanyQuery::table('user_email_settings')->where('user_id', $user->id)->first();
    if (!$setting?->smtp_app_password_encrypted) {
      return false;
    }

    $smtpUsername = $smtpUsername ?: $this->resolveUserEmail($user);
    if (!$smtpUsername) {
      return false;
    }

    // Decrypt stored app password (encrypted at rest).
    $appPassword = null;
    try {
      $appPassword = Crypt::decryptString($setting->smtp_app_password_encrypted);
    } catch (\Throwable $e) {
      return false;
    }

    $appPassword = is_string($appPassword) ? trim($appPassword) : '';
    if ($appPassword === '') {
      return false;
    }

    // Override the shared `smtp` mailer credentials for this request.
    config([
      'mail.mailers.smtp.username' => $smtpUsername,
      'mail.mailers.smtp.password' => $appPassword,
      'mail.from.address' => $smtpUsername,
      'mail.from.name' => is_string($user->name) ? trim($user->name) : (string) ($user->first_name ?? 'User'),
    ]);

    // Force Laravel to rebuild the transport with the new credentials.
    Mail::purge('smtp');

    return true;
  }

  /**
   * Get CC recipient emails configured for the given user.
   */
  public function getCcRecipientEmails(User $user): array
  {
    $recipients = \App\Support\CompanyQuery::table('user_email_cc_recipients')
      ->where('user_id', $user->id)
      ->join('users as u', 'u.id', '=', 'user_email_cc_recipients.recipient_user_id')
      ->get(['u.email', 'u.username']);

    $recipientEmails = array_filter(array_map(function ($row) {
      $email = is_string($row->email) ? trim($row->email) : '';
      if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
      }

      $usernameAsEmail = is_string($row->username) ? trim($row->username) : '';
      return ($usernameAsEmail !== '' && filter_var($usernameAsEmail, FILTER_VALIDATE_EMAIL)) ? $usernameAsEmail : null;
    }, $recipients->all()));

    // Ensure unique + reindex.
    $recipientEmails = array_values(array_unique($recipientEmails));

    return $recipientEmails;
  }
}

