<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountRequest;
use App\Http\Requests\UpdateEmailAccountRequest;
use App\Models\EmailAccount;
use App\Models\User;
use App\Support\CompanyAuthRedirect;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Flash;

class EmailAccountController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth');
  }

  public function index(Request $request)
  {
    $this->authorize('viewAny', EmailAccount::class);

    if (!auth()->check()) {
      return redirect()->to(CompanyAuthRedirect::url($request));
    }

    $query = EmailAccount::query()->withCount('users');

    if ($request->filled('email')) {
      $query->where('email', 'like', '%' . $request->email . '%');
    }
    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    $accounts = $query->with('users:id')->withCount('users')->orderByDesc('id')->get();
    $accountRoute = 'settings-panel.email-accounts';

    if ($request->ajax()) {
      return response()->json([
        'tableData' => view('email_accounts.table', [
          'accounts' => $accounts,
          'accountRoute' => $accountRoute,
          'embeddedEmailAccountManager' => true,
        ])->render(),
      ]);
    }

    return view('email_accounts.index', compact('accounts', 'accountRoute'));
  }

  public function create()
  {
    $this->authorize('create', EmailAccount::class);

    return redirect()->route('settings-panel.email-accounts.index', [
      'company_slug' => request()->route('company_slug'),
    ])->with('open_create_modal', true);
  }

  public function store(StoreEmailAccountRequest $request)
  {
    $validated = $request->validated();

    try {
      DB::beginTransaction();

      $account = new EmailAccount();
      $account->email = strtolower(trim($validated['email']));
      $account->app_password = $validated['app_password'];
      $account->display_name = $validated['display_name'] ?? null;
      $account->status = $validated['status'];
      $account->save();

      $account->users()->sync($this->filterUserIds($validated['user_ids'] ?? []));

      DB::commit();

      Flash::success('Email account created successfully.');

      return $this->redirectAfterAction($request);
    } catch (\Throwable $e) {
      DB::rollBack();
      Flash::error('Error: ' . $e->getMessage());

      return redirect()->route('settings-panel.email-accounts.index', [
        'company_slug' => $request->route('company_slug'),
      ])->withInput()->with('open_create_modal', true);
    }
  }

  public function edit($company_slug, EmailAccount $email_account)
  {
    $this->authorize('update', $email_account);

    return redirect()->route('settings-panel.email-accounts.index', [
      'company_slug' => $company_slug,
    ])->with('open_edit_account_id', $email_account->id);
  }

  public function update(UpdateEmailAccountRequest $request, $company_slug, EmailAccount $email_account)
  {
    $validated = $request->validated();

    try {
      DB::beginTransaction();

      $email_account->email = strtolower(trim($validated['email']));
      if (!empty($validated['app_password'])) {
        $email_account->app_password = $validated['app_password'];
      }
      $email_account->display_name = $validated['display_name'] ?? null;
      $email_account->status = $validated['status'];
      $email_account->save();

      $email_account->users()->sync($this->filterUserIds($validated['user_ids'] ?? []));

      DB::commit();

      Flash::success('Email account updated successfully.');

      return $this->redirectAfterAction($request);
    } catch (\Throwable $e) {
      DB::rollBack();
      Flash::error('Error: ' . $e->getMessage());

      return redirect()->route('settings-panel.email-accounts.index', [
        'company_slug' => $company_slug,
      ])->withInput()->with('open_edit_account_id', $email_account->id);
    }
  }

  public function destroy($company_slug, EmailAccount $email_account)
  {
    $this->authorize('delete', $email_account);

    try {
      DB::beginTransaction();
      $email_account->users()->detach();
      $email_account->delete();
      DB::commit();

      Flash::success('Email account deleted successfully.');
    } catch (\Throwable $e) {
      DB::rollBack();
      Flash::error('Error: ' . $e->getMessage());
    }

    return redirect()->route('settings-panel.email-accounts.index', [
      'company_slug' => $company_slug,
    ]);
  }

  private function filterUserIds(array $userIds): array
  {
    $userIds = array_values(array_unique(array_map('intval', $userIds)));
    if ($userIds === []) {
      return [];
    }

    $query = User::query()->whereIn('id', $userIds);
    $companyId = CompanyContext::id();
    if ($companyId !== null) {
      $query->where('company_id', $companyId);
    }

    return $query->pluck('id')->all();
  }

  private function redirectAfterAction(Request $request)
  {
    if ($request->filled('return_to')) {
      return redirect()->to($request->input('return_to'));
    }

    return redirect()->route('settings-panel.email-accounts.index', [
      'company_slug' => $request->route('company_slug'),
    ]);
  }
}
