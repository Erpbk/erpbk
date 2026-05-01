<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminAccountFixingController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->hasRole('Super Admin'), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $search = trim((string) $request->get('search', ''));

        $accounts = Accounts::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('account_code', 'like', '%' . $search . '%')
                        ->orWhere('account_type', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('is_fixed')
            ->orderBy('account_code')
            ->paginate(50)
            ->withQueryString();

        $parents = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where(function ($query): void {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->orderBy('account_code')
            ->get(['id', 'name', 'account_code']);

        $accountTypes = \App\Helpers\Accounts::AccountTypes();

        return view('admin.accounts.fixed_settings', compact('accounts', 'search', 'parents', 'accountTypes'));
    }

    public function create(): View
    {
        $this->ensureSuperAdmin();

        $parents = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where(function ($query): void {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->orderBy('account_code')
            ->get(['id', 'name', 'account_code']);

        return view('admin.accounts.create_fixed', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', Rule::exists('accounts', 'id')],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:1,2'],
            'notes' => ['nullable', 'string'],
        ]);

        $account = new Accounts();
        $account->name = $validated['name'];
        $account->account_type = $validated['account_type'];
        $account->parent_id = $validated['parent_id'] ?? null;
        $account->opening_balance = $validated['opening_balance'] ?? 0;
        $account->status = (int) ($validated['status'] ?? 1);
        $account->notes = $validated['notes'] ?? null;
        $account->is_fixed = true;
        $account->is_locked = 0;
        $account->save();

        if (empty($account->account_code)) {
            $account->account_code = str_pad((string) $account->id, 4, '0', STR_PAD_LEFT);
            $account->save();
        }

        return redirect()->route('admin.accounts.fixed.index')->with('success', 'Fixed account created successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $account = Accounts::withoutGlobalScopes(['company', 'branch'])->findOrFail($id);

        if ((bool) $account->is_fixed) {
            $childAccountsCount = Accounts::withoutGlobalScopes(['company', 'branch'])
                ->where('parent_id', $account->id)
                ->count();

            if ($childAccountsCount > 0) {
                return back()->with('error', "Fixed account cannot be edited because it has {$childAccountsCount} child account(s).");
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', Rule::exists('accounts', 'id')],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:1,2'],
            'notes' => ['nullable', 'string'],
        ]);

        $account->name = $validated['name'];
        $account->account_type = $validated['account_type'];
        $account->parent_id = $validated['parent_id'] ?? null;
        $account->opening_balance = $validated['opening_balance'] ?? 0;
        $account->status = (int) ($validated['status'] ?? 1);
        $account->notes = $validated['notes'] ?? null;
        $account->save();

        return redirect()->route('admin.accounts.fixed.index')->with('success', 'Account updated successfully.');
    }

    public function toggle(Request $request, int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $account = Accounts::withoutGlobalScopes(['company', 'branch'])->findOrFail($id);
        $account->is_fixed = !$account->is_fixed;
        $account->save();

        return back()->with(
            'success',
            $account->is_fixed
                ? 'Account marked as fixed and shared across all companies.'
                : 'Account unmarked as fixed.'
        );
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $account = Accounts::withoutGlobalScopes(['company', 'branch'])->findOrFail($id);

        if (!(bool) $account->is_fixed) {
            return back()->with('error', 'Only fixed accounts can be deleted from this page.');
        }

        $childAccountsCount = Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where('parent_id', $account->id)
            ->count();

        if ($childAccountsCount > 0) {
            return back()->with('error', "Cannot delete fixed account. It has {$childAccountsCount} child account(s).");
        }

        $account->delete();

        return back()->with('success', 'Fixed account deleted successfully.');
    }
}
