<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Accounts as AccountsHelper;
use App\Http\Controllers\Controller;
use App\Models\AccountCustomField;
use App\Models\Accounts;
use App\Models\GlobalAccount;
use App\Repositories\AccountsRepository;
use App\Services\GlobalAccountResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminGlobalAccountsController extends Controller
{
    public function __construct(
        private readonly GlobalAccountResolver $resolver,
        private readonly AccountsRepository $accountsRepository
    ) {}

    private function ensureSuperAdmin(): void
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->hasRole('Super Admin'), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $search = trim((string) $request->get('search', ''));

        $globalAccounts = GlobalAccount::query()
            ->with(['account' => fn ($q) => $q->withoutGlobalScopes(['company', 'branch'])])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('code', 'like', '%' . $search . '%')
                        ->orWhere('label', 'like', '%' . $search . '%')
                        ->orWhere('account_type', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('code')
            ->paginate(50)
            ->withQueryString();

        return view('admin.global_accounts.index', compact('globalAccounts', 'search'));
    }

    public function create(): View
    {
        $this->ensureSuperAdmin();

        $accountTypes = AccountsHelper::AccountTypes();
        $existingAccountIds = GlobalAccount::all()->pluck('account_id')->toArray();
        $parents = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where(function ($query): void {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->whereNull('company_id')
            ->whereNotIn('id', $existingAccountIds)
            ->orderBy('account_code')
            ->get(['id', 'name', 'account_code']);

        return view('admin.global_accounts.create', compact('accountTypes', 'parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', 'regex:/^[A-Z][A-Z0-9_]*$/', 'unique:global_accounts,code'],
            'label' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'link_mode' => ['required', 'in:create,link'],
            'account_type' => ['required', 'string', 'max:50'],
            'account_id' => ['required_if:link_mode,link', 'nullable', Rule::exists('accounts', 'id')],
            'account_name' => ['required_if:link_mode,create', 'nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('accounts', 'id')],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:1,2'],
            'notes' => ['nullable', 'string'],
        ]);

        $accountId = null;

        if ($validated['link_mode'] === 'link') {
            $accountId = (int) $validated['account_id'];
            $this->enforceSharedAccount($accountId);
            $this->markAccountFixed($accountId);
        } else {
            $account = new Accounts();
            $account->name = $validated['account_name'];
            $account->account_type = $validated['account_type'];
            $account->parent_id = $validated['parent_id'] ?? null;
            $account->opening_balance = $validated['opening_balance'] ?? 0;
            $account->status = (int) ($validated['status'] ?? 1);
            $account->notes = $validated['notes'] ?? null;
            $account->is_fixed = true;
            $account->is_locked = 0;
            $account->company_id = null;
            $account->save();

            if (empty($account->account_code)) {
                $account->account_code = str_pad((string) $account->id, 4, '0', STR_PAD_LEFT);
                $account->save();
            }

            $accountId = $account->id;
        }

        GlobalAccount::create([
            'code' => $validated['code'],
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
            'account_id' => $accountId,
            'account_type' => $validated['account_type'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->resolver->flushCache();

        return redirect()->route('admin.global-accounts.index')->with('success', 'Global account created successfully.');
    }

    public function edit(GlobalAccount $globalAccount): View
    {
        $this->ensureSuperAdmin();

        $globalAccount->load(['account' => fn ($q) => $q->withoutGlobalScopes(['company', 'branch'])]);

        $accountTypes = AccountsHelper::AccountTypes();
        $parents = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where(function ($query): void {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->whereNull('company_id')
            ->orderBy('account_code')
            ->get(['id', 'name', 'account_code']);

        return view('admin.global_accounts.edit', compact('globalAccount', 'accountTypes', 'parents'));
    }

    public function update(Request $request, GlobalAccount $globalAccount): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'link_mode' => ['required', 'in:keep,create,link'],
            'account_type' => ['required', 'string', 'max:50'],
            'account_id' => ['required_if:link_mode,link', 'nullable', Rule::exists('accounts', 'id')],
            'account_name' => ['required_if:link_mode,create', 'nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('accounts', 'id')],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:1,2'],
            'notes' => ['nullable', 'string'],
        ]);

        $accountId = $globalAccount->account_id;

        if ($validated['link_mode'] === 'link') {
            $newAccountId = (int) $validated['account_id'];
            $this->enforceSharedAccount($newAccountId, $globalAccount->id);
            $this->guardAccountChange($globalAccount, $newAccountId);
            $this->markAccountFixed($newAccountId);
            $accountId = $newAccountId;
        } elseif ($validated['link_mode'] === 'create') {
            $account = new Accounts();
            $account->name = $validated['account_name'];
            $account->account_type = $validated['account_type'];
            $account->parent_id = $validated['parent_id'] ?? null;
            $account->opening_balance = $validated['opening_balance'] ?? 0;
            $account->status = (int) ($validated['status'] ?? 1);
            $account->notes = $validated['notes'] ?? null;
            $account->is_fixed = true;
            $account->is_locked = 0;
            $account->company_id = null;
            $account->save();

            if (empty($account->account_code)) {
                $account->account_code = str_pad((string) $account->id, 4, '0', STR_PAD_LEFT);
                $account->save();
            }

            $accountId = $account->id;
        }

        $globalAccount->update([
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
            'account_id' => $accountId,
            'account_type' => $validated['account_type'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->resolver->flushCache($globalAccount->code);

        return redirect()->route('admin.global-accounts.index')->with('success', 'Global account updated successfully.');
    }

    public function destroy(GlobalAccount $globalAccount): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $code = $globalAccount->code;
        $globalAccount->delete();
        $this->resolver->flushCache($code);

        return redirect()->route('admin.global-accounts.index')->with('success', 'Global account deleted successfully.');
    }

    public function accountsByType(string $type): JsonResponse
    {
        $this->ensureSuperAdmin();

        $allowedTypes = array_keys(AccountsHelper::AccountTypes());
        abort_unless(in_array($type, $allowedTypes, true), 404);

        $accounts = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where('account_type', $type)
            ->whereNull('company_id')
            ->orderBy('account_code')
            ->get(['id', 'name', 'account_code'])
            ->mapWithKeys(fn (Accounts $account) => [
                $account->id => trim(($account->account_code ?? '') . ' — ' . $account->name),
            ]);

        return response()->json($accounts);
    }

    public function editLinkedAccount(GlobalAccount $globalAccount): View
    {
        $this->ensureSuperAdmin();
        abort_unless($globalAccount->account_id, 404);

        $accounts = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->findOrFail($globalAccount->account_id);

        $parents = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where(function ($query): void {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->whereNull('company_id')
            ->get(['id', 'name', 'parent_id'])
            ->groupBy('parent_id');

        $customFields = AccountCustomField::orderBy('display_order')->orderBy('id')->get();

        return view('admin.global_accounts.linked_account_edit', compact('accounts', 'parents', 'customFields', 'globalAccount'));
    }

    public function updateLinkedAccount(Request $request, GlobalAccount $globalAccount): JsonResponse
    {
        $this->ensureSuperAdmin();
        abort_unless($globalAccount->account_id, 404);

        $validated = $request->validate(Accounts::$rules);

        $accounts = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->findOrFail($globalAccount->account_id);

        $childAccountsCount = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where('parent_id', $accounts->id)
            ->count();

        if ($childAccountsCount > 0) {
            return response()->json([
                'errors' => ['error' => "Fixed account cannot be edited because it has {$childAccountsCount} child account(s)."],
            ], 422);
        }

        $input = $request->except(['custom_field_values', 'is_fixed']);
        $input['is_fixed'] = true;
        $input['company_id'] = null;

        $accounts = $this->accountsRepository->update($input, $globalAccount->account_id);

        if ($accounts) {
            $this->saveCustomFieldValues($accounts, $request->input('custom_field_values', []));
            $row = AccountsHelper::getRef(['ref_name' => $accounts->ref_name, 'ref_id' => $accounts->ref_id]);
            if (isset($row)) {
                $row->name = $accounts->name;
                $row->status = $accounts->status;
                $row->save();
            }
        }

        return response()->json(['message' => 'Account updated successfully.']);
    }

    private function saveCustomFieldValues(Accounts $account, array $values): void
    {
        $validIds = AccountCustomField::pluck('id')->flip()->all();
        $filtered = [];
        foreach ($values as $fieldId => $value) {
            if (isset($validIds[$fieldId])) {
                $filtered[(string) $fieldId] = $value;
            }
        }
        $account->custom_field_values = $filtered;
        $account->save();
    }

    private function enforceSharedAccount(int $accountId, ?int $exceptGlobalAccountId = null): void
    {
        $account = Accounts::withoutGlobalScopes(['company', 'branch'])->findOrFail($accountId);

        if ($account->company_id !== null) {
            throw ValidationException::withMessages([
                'account_id' => 'Only shared accounts (company_id NULL) can be linked to a global account.',
            ]);
        }

        $existing = GlobalAccount::query()
            ->where('account_id', $accountId)
            ->when($exceptGlobalAccountId, fn ($q) => $q->where('id', '!=', $exceptGlobalAccountId))
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'account_id' => "Account is already linked to global code [{$existing->code}].",
            ]);
        }
    }

    private function markAccountFixed(int $accountId): void
    {
        $account = Accounts::withoutGlobalScopes(['company', 'branch'])->findOrFail($accountId);
        $account->is_fixed = true;
        $account->company_id = null;
        $account->save();
    }

    private function guardAccountChange(GlobalAccount $globalAccount, int $newAccountId): void
    {
        if ((int) $globalAccount->account_id === $newAccountId) {
            return;
        }

        if ($globalAccount->account_id === null) {
            return;
        }

        $hasTransactions = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->find($globalAccount->account_id)
            ?->transactions()
            ->exists();

        if ($hasTransactions) {
            throw ValidationException::withMessages([
                'account_id' => 'Cannot change linked account because the current account has ledger transactions.',
            ]);
        }
    }
}
