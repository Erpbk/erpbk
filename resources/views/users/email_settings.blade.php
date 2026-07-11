@extends($layout ?? 'layouts.app')

@php
$isPanel = (View::shared('settings_panel') ?? false);
$updateRoute = $isPanel ? 'settings-panel.email-settings.update' : 'user.email-settings.update';
$cancelRoute = $isPanel ? 'settings-panel.profile' : 'profile';
$emailAccountsRoute = $isPanel ? 'settings-panel.email-accounts.index' : null;
@endphp

@section('title', 'Email Preferences')

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title mb-0">Email Preferences</h4>
      </div>
      @canany(['settings_email_edit','settings_email_create'])
      <div class="card-body">
        <div class="row mb-4">
          <div class="col-md-12">
            <h5 class="mb-2">Your Sender Email Accounts</h5>
            @if($assignedEmailAccounts->isEmpty())
            <div class="alert alert-warning mb-0">
              No active email accounts are assigned to you.
              @if(auth()->user()->isAdmin() && $emailAccountsRoute)
              <a href="{{ route($emailAccountsRoute, ['company_slug' => request()->route('company_slug')]) }}">Manage email accounts</a>
              @else
              Ask an administrator to assign an email account to your user.
              @endif
            </div>
            @else
            <ul class="list-group">
              @foreach($assignedEmailAccounts as $account)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ $account->displayLabel() }}</span>
                <span class="badge bg-success">Active</span>
              </li>
              @endforeach
            </ul>
            @endif

            @if(auth()->user()->isAdmin() && $emailAccountsRoute)
            <div class="mt-3">
              <a href="{{ route($emailAccountsRoute, ['company_slug' => request()->route('company_slug')]) }}" class="btn btn-outline-primary btn-sm">
                Manage Email Accounts
              </a>
            </div>
            @endif
          </div>
        </div>

        <form method="POST" action="{{ route($updateRoute) }}">
          @csrf

          <div class="row">
            <div class="col-md-8 mb-4">
              <label class="form-label">Default CC Recipients</label>
              <div class="form-text mb-2">
                These addresses are pre-filled in the CC field when you send rider emails. You can add or remove recipients before sending.
              </div>

              <div class="border rounded p-3" style="max-height: 360px; overflow: auto;">
                @forelse($otherUsers as $u)
                @php
                $checked = in_array($u->id, (array) $ccRecipientIds, true);
                $displayEmail = $u->email ?: $u->username;
                $displayName = $u->name ?: trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
                @endphp
                <div class="form-check mb-2">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    name="cc_recipient_user_ids[]"
                    value="{{ $u->id }}"
                    id="cc_user_{{ $u->id }}"
                    {{ $checked ? 'checked' : '' }}>
                  <label class="form-check-label" for="cc_user_{{ $u->id }}">
                    {{ $displayName ?: 'User' }}
                    @if($displayEmail)
                    <span class="text-muted">({{ $displayEmail }})</span>
                    @endif
                  </label>
                </div>
                @empty
                <div class="text-muted">No other users found.</div>
                @endforelse
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route($cancelRoute) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
              Save Preferences
            </button>
          </div>
        </form>
      </div>
      @else
      <div class="card-body">
        <div class="alert alert-info">
          You are not authorized to edit email settings.
        </div>
      </div>
      @endcanany
    </div>
  </div>
</div>
@endsection
