<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Flash;

class UserEmailSettingsController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
  }

  public function edit()
  {
    $user = Auth::user();

    $ccRecipientIds = \App\Support\CompanyQuery::table('user_email_cc_recipients')
      ->where('user_id', $user->id)
      ->pluck('recipient_user_id')
      ->all();

    $otherUsers = User::query()
      ->where('id', '!=', $user->id)
      ->orderBy('name')
      ->get(['id', 'name', 'first_name', 'last_name', 'email', 'username']);

    $setting = \App\Support\CompanyQuery::table('user_email_settings')->where('user_id', $user->id)->first();
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

    $validated = $request->validate([
      'smtp_app_password' => 'nullable|string|min:6',
      'cc_recipient_user_ids' => 'nullable|array',
      'cc_recipient_user_ids.*' => 'integer|distinct|exists:users,id',
    ]);

    $ccRecipientIds = $request->input('cc_recipient_user_ids', []);
    // Never allow selecting self as CC recipient.
    $ccRecipientIds = array_values(array_filter($ccRecipientIds, fn ($id) => (int) $id !== (int) $user->id));

    // Update SMTP app password (encrypted) if user provided one.
    if ($request->filled('smtp_app_password')) {
      $encrypted = Crypt::encryptString($request->input('smtp_app_password'));

      \App\Support\CompanyQuery::table('user_email_settings')->updateOrInsert(
        ['user_id' => $user->id],
        ['smtp_app_password_encrypted' => $encrypted, 'updated_at' => now(), 'created_at' => now()]
      );
    }

    // Sync CC recipients.
    \App\Support\CompanyQuery::table('user_email_cc_recipients')->where('user_id', $user->id)->delete();
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
      \App\Support\CompanyQuery::table('user_email_cc_recipients')->insert($rows);
    }

    Flash::success('Email settings saved.');

    $redirectRoute = request()->routeIs('settings-panel.email-settings.update')
      ? 'settings-panel.email-settings.edit'
      : 'user.email-settings.edit';

    return redirect()->route($redirectRoute);
  }
}

