<aside class="main-sidebar sidebar-dark-primary elevation-4">
    @php
    $tenantCompany = view()->shared('currentCompany');
    $tenantLogo = !empty($tenantCompany?->logo) ? asset('storage/' . $tenantCompany->logo) : 'https://assets.infyom.com/logo/blue_logo_150x150.png';
    @endphp
    <a href="{{ route('home') }}" class="brand-link">
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