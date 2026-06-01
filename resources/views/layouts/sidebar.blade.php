<aside class="main-sidebar sidebar-dark-primary elevation-4">
    @php
    $tenantCompany = view()->shared('currentCompany');
    $tenantLogo = !empty($tenantCompany?->logo) ? storage_url($tenantCompany->logo) : 'https://assets.infyom.com/logo/blue_logo_150x150.png';
    $companySlug = request()->route('company_slug') ?? session('company_slug');
    $homeLink = auth('admin')->check()
        ? route('admin.dashboard')
        : ($companySlug ? route('home', ['company_slug' => $companySlug]) : url('/'));
    @endphp
    <a href="{{ $homeLink }}" class="brand-link">
        <img src="{{ $tenantLogo }}"
            alt="{{ $tenantCompany?->name ?? config('app.name') }}"
            class="brand-image img-circle elevation-3">
        <span class="brand-text font-weight-light">{{ $tenantCompany?->name ?? config('app.name') }}</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                @include('layouts.menu')
            </ul>
        </nav>
    </div>

</aside>