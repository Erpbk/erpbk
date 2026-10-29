<?php

namespace App\Http\Controllers;

use App\Support\VoucherApprovalRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Admin UI explaining / linking Voucher Approval Permission assignment
 * (managed through the existing Role Permissions matrix).
 */
class VoucherApprovalPermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, string $company_slug): View
    {
        abort_unless(
            Auth::user()?->isAdmin() || user_can('settings_roles_view') || user_can('settings_voucher_approvals_view'),
            403
        );

        $types = VoucherApprovalRegistry::types();
        $globals = config('voucher_approval.permissions', []);

        return view('voucher_approvals.permissions', compact('types', 'globals'));
    }
}
