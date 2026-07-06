<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Email\EmailAccountService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
    $assignedEmailAccounts = app(EmailAccountService::class)->getActiveAssignedAccounts($user);

    return view('users.email_settings', [
      'user' => $user,
      'otherUsers' => $otherUsers,
      'ccRecipientIds' => $ccRecipientIds,
      'assignedEmailAccounts' => $assignedEmailAccounts,
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
      'cc_recipient_user_ids' => 'nullable|array',
      'cc_recipient_user_ids.*' => ['integer', 'distinct', $ccUserRule],
    ]);

    $ccRecipientIds = $request->input('cc_recipient_user_ids', []);
    $ccRecipientIds = array_values(array_filter($ccRecipientIds, fn($id) => (int) $id !== (int) $user->id));

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

    Flash::success('Email preferences saved.');

    $redirectRoute = request()->routeIs('settings-panel.email-settings.update')
      ? 'settings-panel.email-settings.edit'
      : 'user.email-settings.edit';

    return redirect()->route($redirectRoute);
  }
}
