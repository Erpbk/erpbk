@php
$containerNav = ($configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
$navbarDetached = ($navbarDetached ?? '');
$isAdminRoute = request()->routeIs('admin.*');
$adminUser = auth('admin')->user();
$isAdminSession = $isAdminRoute && (bool) $adminUser;
$companySlug = request()->route('company_slug') ?? session('company_slug');
$logoutAction = $isAdminSession
    ? route('admin.logout')
    : ($companySlug ? route('company.logout', ['company_slug' => $companySlug]) : route('logout'));
$homeLink = $isAdminSession
? route('admin.dashboard')
: ($companySlug ? route('home', ['company_slug' => $companySlug]) : url('/'));
@endphp

<!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme" id="layout-navbar">
  @endif
  @if(isset($navbarDetached) && $navbarDetached == '')
  <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="{{$containerNav}}">
      @endif

      <!--  Brand demo (display only for navbar-full and hide on below xl) -->
      @if(isset($navbarFull))
      <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
        <a href="{{ $homeLink }}" class="app-brand-link gap-2">
          <span class="app-brand-logo demo">
            @include('_partials.macros',["height"=>20])
          </span>
          <span class="app-brand-text demo menu-text fw-bold">{{config('variables.templateName')}}</span>
        </a>
      </div>
      @endif

      <!-- ! Not required for layout-without-menu -->
      @if(!isset($navbarHideToggle))
      <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
          <i class="ti ti-menu-2 ti-sm"></i>
        </a>
      </div>
      @endif

      <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

        @if($configData['hasCustomizer'] == true)
        <!-- Style Switcher -->
        <div class="navbar-nav align-items-center">
          <div class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <i class='ti ti-md'></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-start dropdown-styles">
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                  <span class="align-middle"><i class='ti ti-sun me-2'></i>Light</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                  <span class="align-middle"><i class="ti ti-moon me-2"></i>Dark</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                  <span class="align-middle"><i class="ti ti-device-desktop me-2"></i>System</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
        <!--/ Style Switcher -->
        @endif

        <ul class="navbar-nav flex-row align-items-center ms-auto">

          @if(!$isAdminSession && Auth::check())
          <li class="nav-item navbar-dropdown dropdown-notifications dropdown me-2 me-lg-3 position-relative" id="nav-notifications">
            <a class="nav-link dropdown-toggle hide-arrow position-relative" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
              <i class="ti ti-bell ti-md"></i>
              <span class="badge rounded-pill bg-danger badge-notifications position-absolute top-0 start-100 translate-middle d-none" style="font-size:0.65rem;" id="nav-notification-badge">0</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end py-0" style="min-width: 24rem; max-width: 28rem;">
              <li class="dropdown-header d-flex align-items-center justify-content-between">
                <span class="fw-medium">{{ __('Notifications') }}</span>
                <a href="{{ route('notifications.index', ['company_slug' => $companySlug]) }}" class="small">{{ __('View all') }}</a>
              </li>
              <li><hr class="dropdown-divider my-0"></li>
              <li>
                <div id="nav-notification-list" class="list-group list-group-flush" style="max-height: 26rem; overflow-y: auto;">
                  <div class="list-group-item text-muted small">{{ __('Loading…') }}</div>
                </div>
              </li>
              <li><hr class="dropdown-divider my-0"></li>
              <li class="dropdown-footer text-center py-2">
                <form action="{{ route('notifications.read-all', ['company_slug' => $companySlug]) }}" method="post" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-link">{{ __('Mark all as read') }}</button>
                </form>
              </li>
            </ul>
          </li>
          @endif

          <li class="nav-item me-2 me-lg-3">
            @if(!$isAdminSession && Auth::check() && Auth::user()->isAdmin())
            <a
              class="nav-link"
              href="{{ $companySlug ? route('settings-panel.index', ['company_slug' => $companySlug]) : url('/') }}"
              target="_blank"
              rel="noopener"
              title="{{ __('Settings') }}">
              <i class="ti ti-settings ti-md"></i>
            </a>
            @endif
          </li>

          <!-- User -->
          <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <div class="avatar avatar-online">
                @php
                if ($isAdminSession) {
                $image_name = 'default.png';
                } elseif (Auth::check() && auth()->user()->image_name) {
                $image_name = auth()->user()->image_name;
                } else {
                $image_name = 'default.png';
                }
                @endphp
                <img src="{{ user_avatar_url($image_name) }}" alt class="h-auto rounded-circle">
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="{{ $isAdminSession ? route('admin.dashboard') : (Route::has('profile.show') ? route('profile.show') : 'javascript:void(0);') }}">
                  <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                      <div class="avatar avatar-online">
                        <img src="{{ user_avatar_url($image_name) }}" alt class="h-auto rounded-circle">
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <span class="fw-medium d-block">
                        @if ($isAdminSession)
                        {{ $adminUser->name }}
                        @elseif (Auth::check())
                        {{ Auth::user()->name }}
                        @else
                        John Doe
                        @endif
                      </span>
                      <small class="text-muted">
                        @if ($isAdminSession)
                        {{ $adminUser->roles->pluck('name')->first() ?? __('Admin') }}
                        @elseif (Auth::check())
                        {{ Auth::user()->roles->pluck('name','name')->first() }}
                        @endif
                      </small>
                    </div>
                  </div>
                </a>
              </li>
              <li>
                <div class="dropdown-divider"></div>
              </li>
              @if (!$isAdminSession)
              <li>
                <a class="dropdown-item" href="{{route('profile') }}">
                  <i class="ti ti-user-check me-2 ti-sm"></i>
                  <span class="align-middle">My Profile</span>
                </a>
              </li>
              @endif
              @if (Auth::check())
              {{-- <li>
                <a class="dropdown-item" href="{{ route('api-tokens.index') }}">
              <i class='ti ti-key me-2 ti-sm'></i>
              <span class="align-middle">API Tokens</span>
              </a>
          </li> --}}
          @endif
          {{-- <li>
                <a class="dropdown-item" href="javascript:void(0);">
                  <span class="d-flex align-items-center align-middle">
                    <i class="flex-shrink-0 ti ti-credit-card me-2 ti-sm"></i>
                    <span class="flex-grow-1 align-middle">Billing</span>
                    <span class="flex-shrink-0 badge badge-center rounded-pill bg-label-danger w-px-20 h-px-20">2</span>
                  </span>
                </a>
              </li> --}}

          <li>
            <div class="dropdown-divider"></div>
          </li>
          @if ($isAdminSession || Auth::check())
          <li>
            <form method="POST" action="{{ $logoutAction }}" class="d-inline w-100">
              @csrf
              <button type="submit" class="dropdown-item border-0 bg-transparent w-100 text-start">
                <i class='ti ti-logout me-2'></i>
                <span class="align-middle">Logout</span>
              </button>
            </form>
          </li>
          @else
          <li>
            <a class="dropdown-item" href="{{ \App\Support\CompanyAuthRedirect::url(request()) }}">
              <i class='ti ti-login me-2'></i>
              <span class="align-middle">Login</span>
            </a>
          </li>
          @endif
        </ul>
        </li>
        <!--/ User -->
        </ul>
      </div>

      @if(!isset($navbarDetached))
    </div>
    @endif
  </nav>
  <!-- / Navbar -->
@if(!$isAdminSession && Auth::check() && !empty($companySlug))
<script>
(function () {
  var badge = document.getElementById('nav-notification-badge');
  var list = document.getElementById('nav-notification-list');
  if (!badge || !list) return;

  var countUrl = @json(route('notifications.unread-count', ['company_slug' => $companySlug]));
  var dropdownUrl = @json(route('notifications.dropdown', ['company_slug' => $companySlug]));
  var csrf = @json(csrf_token());

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function refreshCount() {
    fetch(countUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var n = parseInt(data.count || 0, 10);
        if (n > 0) {
          badge.textContent = n > 99 ? '99+' : String(n);
          badge.classList.remove('d-none');
        } else {
          badge.classList.add('d-none');
        }
      }).catch(function () {});
  }

  function openNotificationAction(item) {
    var action = item.action_url || '';
    var mode = item.action_mode || 'navigate';
    if (!action) {
      refreshCount();
      refreshDropdown();
      return;
    }

    // Close the bell dropdown before opening a record.
    var dropdownToggle = document.querySelector('#nav-notifications > a');
    if (dropdownToggle && window.bootstrap && bootstrap.Dropdown) {
      var dd = bootstrap.Dropdown.getInstance(dropdownToggle);
      if (dd) dd.hide();
    }

    if (mode === 'modal' && window.jQuery) {
      var $btn = window.jQuery('<a href="javascript:void(0);" class="show-modal d-none"></a>');
      $btn.attr('data-action', action);
      $btn.attr('data-size', item.action_size || 'xl');
      $btn.attr('data-title', item.action_title || item.title || 'Details');
      window.jQuery('body').append($btn);
      $btn.trigger('click');
      $btn.remove();
      refreshCount();
      return;
    }

    window.location.href = action;
  }

  function refreshDropdown() {
    fetch(dropdownUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var items = data.items || [];
        if (!items.length) {
          list.innerHTML = '<div class="list-group-item text-muted small">No notifications yet.</div>';
          return;
        }
        list.innerHTML = items.map(function (item) {
          var unread = item.is_unread ? ' list-group-item-warning' : '';
          var body = item.body
            ? '<div class="text-muted small mt-1" style="white-space: normal; overflow-wrap: anywhere;">' + escapeHtml(item.body) + '</div>'
            : '';
          var openHint = item.action_url
            ? '<div class="text-primary small mt-1">' + (item.action_mode === 'modal' ? 'Open record' : 'Open') + '</div>'
            : '';
          return '<button type="button" class="list-group-item list-group-item-action text-start' + unread + '" data-notification-id="' + escapeHtml(item.id) + '">' +
            '<div class="fw-medium" style="white-space: normal; overflow-wrap: anywhere;">' + escapeHtml(item.title) + '</div>' +
            body + openHint +
            '</button>';
        }).join('');

        list.querySelectorAll('[data-notification-id]').forEach(function (el, idx) {
          el.addEventListener('click', function () {
            var item = items[idx];
            if (!item) return;
            var url = item.mark_read_url;
            fetch(url, {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
              },
              credentials: 'same-origin'
            }).finally(function () {
              openNotificationAction(item);
            });
          });
        });
      }).catch(function () {
        list.innerHTML = '<div class="list-group-item text-muted small">Unable to load notifications.</div>';
      });
  }

  refreshCount();
  setInterval(refreshCount, 60000);

  var toggle = document.querySelector('#nav-notifications > a');
  if (toggle) {
    toggle.addEventListener('show.bs.dropdown', refreshDropdown);
  }
})();
</script>
@endif
