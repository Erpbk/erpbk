<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Email\UserEmailService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Flash;

class UserEmailSettingsController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
    $this->middleware('permission:settings_email_view|settings_email_create')->only('edit');
    $this->middleware('permission:settings_email_edit|settings_email_create')->only('update');
  }

  public function edit()
  {
    $user = Auth::user();

    $ccRecipientIds = DB::table('user_email_cc_recipients')
      ->where('user_id', $user->id)
      ->pluck('recipient_user_id')
      ->all();

    $otherUsersQuery = User::query()
      ->where('id', '!=', $user->id)
      ->orderBy('name');

    $companyId = CompanyContext::id();
    if ($companyId !== null) {
      $otherUsersQuery->where('company_id', $companyId);
    }

    $otherUsers = $otherUsersQuery->get(['id', 'name', 'first_name', 'last_name', 'email', 'username']);

    $setting = DB::table('user_email_settings')->where('user_id', $user->id)->first();
    $hasAppPassword = !empty($setting?->smtp_app_password_encrypted);

    return view('users.email_settings', [
      'user' => $user,
      'otherUsers' => $otherUsers,
      'ccRecipientIds' => $ccRecipientIds,
      'hasAppPassword' => $hasAppPassword,
    ]);
  }

  public function update(Request $request)
  {
    $user = Auth::user();

    $companyId = CompanyContext::id();
    $ccUserRule = Rule::exists('users', 'id');
    if ($companyId !== null) {
      $ccUserRule = $ccUserRule->where(fn($query) => $query->where('company_id', $companyId));
    }

    $validated = $request->validate([
      'smtp_app_password' => 'nullable|string|min:6',
      'cc_recipient_user_ids' => 'nullable|array',
      'cc_recipient_user_ids.*' => ['integer', 'distinct', $ccUserRule],
    ]);

    $ccRecipientIds = $request->input('cc_recipient_user_ids', []);
    // Never allow selecting self as CC recipient.
    $ccRecipientIds = array_values(array_filter($ccRecipientIds, fn($id) => (int) $id !== (int) $user->id));

    // Update SMTP app password (encrypted) if user provided one.
    // Row is unique by user_id; do not scope by company_id on lookup (legacy rows may have NULL company_id).
    if ($request->filled('smtp_app_password')) {
      $normalizedPassword = UserEmailService::normalizeGmailAppPassword($request->input('smtp_app_password'));
      if (strlen($normalizedPassword) < 16) {
        return back()
          ->withErrors([
            'smtp_app_password' => 'Gmail App Password must be 16 characters (spaces are removed automatically). Create one under Google Account → Security → App passwords.',
          ])
          ->withInput();
      }

      $encrypted = Crypt::encryptString($normalizedPassword);
      $smtpPayload = [
        'smtp_app_password_encrypted' => $encrypted,
        'updated_at' => now(),
      ];
      if ($companyId !== null) {
        $smtpPayload['company_id'] = $companyId;
      }

      $existingSmtp = DB::table('user_email_settings')->where('user_id', $user->id)->first();
      if ($existingSmtp) {
        DB::table('user_email_settings')->where('id', $existingSmtp->id)->update($smtpPayload);
      } else {
        DB::table('user_email_settings')->insert(array_merge($smtpPayload, [
          'user_id' => $user->id,
          'created_at' => now(),
        ]));
      }
    }

    // Sync CC recipients for this user (one row set per user_id).
    DB::table('user_email_cc_recipients')->where('user_id', $user->id)->delete();
    $rows = [];
    foreach ($ccRecipientIds as $recipientUserId) {
      $rows[] = [
        'user_id' => $user->id,
        'recipient_user_id' => (int) $recipientUserId,
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }
    if (!empty($rows)) {
      \App\Support\CompanyQuery::insert('user_email_cc_recipients', $rows);
    }

    Flash::success('Email settings saved.');

    $redirectRoute = request()->routeIs('settings-panel.email-settings.update')
      ? 'settings-panel.email-settings.edit'
      : 'user.email-settings.edit';

    return redirect()->route($redirectRoute);
  }
}
