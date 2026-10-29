<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\EmailAccount::class => \App\Policies\EmailAccountPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Voucher approval abilities (do not bind as default Vouchers policy — CRUD uses module gates).
        \Illuminate\Support\Facades\Gate::define('voucher.approve', [\App\Policies\VoucherApprovalPolicy::class, 'approve']);
        \Illuminate\Support\Facades\Gate::define('voucher.reject', [\App\Policies\VoucherApprovalPolicy::class, 'reject']);
        \Illuminate\Support\Facades\Gate::define('voucher.forceApprove', [\App\Policies\VoucherApprovalPolicy::class, 'forceApprove']);
        \Illuminate\Support\Facades\Gate::define('voucher.reopen', [\App\Policies\VoucherApprovalPolicy::class, 'reopen']);
        \Illuminate\Support\Facades\Gate::define('voucher.revise', [\App\Policies\VoucherApprovalPolicy::class, 'revise']);
        \Illuminate\Support\Facades\Gate::define('voucher.resubmit', [\App\Policies\VoucherApprovalPolicy::class, 'resubmit']);
        \Illuminate\Support\Facades\Gate::define('voucher.approvalDashboard', [\App\Policies\VoucherApprovalPolicy::class, 'viewDashboard']);
    }
}
