<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAccountFixingController extends Controller
{
    public function index(Request $request): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->hasRole('Super Admin'), 403);

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

        return view('admin.accounts.fixed_settings', compact('accounts', 'search'));
    }

    public function toggle(Request $request, int $id): RedirectResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->hasRole('Super Admin'), 403);

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
}
