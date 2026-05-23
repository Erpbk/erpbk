@extends($layout ?? 'layouts.app')

@php
  $isPanel = (View::shared('settings_panel') ?? false);
  $updateRoute = $isPanel ? 'settings-panel.email-settings.update' : 'user.email-settings.update';
  $cancelRoute = $isPanel ? 'settings-panel.profile' : 'profile';
@endphp

@section('title', 'Email Settings')

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title mb-0">Email Settings</h4>
      </div>

      <div class="card-body">
        <form method="POST" action="{{ route($updateRoute) }}">
          @csrf

          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label">
                Gmail App Password (SMTP)
              </label>
              <input
                type="password"
                name="smtp_app_password"
                class="form-control"
                placeholder="Enter your Gmail app password"
                autocomplete="off"
              />
              <div class="form-text">
                Required before anyone in this company can send rider or employee emails.
                <ol class="mb-0 ps-3">
                  <li>Enable <strong>2-Step Verification</strong> on the Gmail account.</li>
                  <li>Open <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">Google App passwords</a> → create one for <strong>Mail</strong>.</li>
                  <li>Copy the <strong>16-character</strong> password (paste here; spaces are removed automatically).</li>
                  <li>Your user login email must be the <strong>same Gmail address</strong> (e.g. absdevelops.17@gmail.com).</li>
                </ol>
                Do <strong>not</strong> use your normal Gmail login password. Leave blank to keep the existing app password.
              </div>
              @error('smtp_app_password')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
              @if($hasAppPassword)
                <div class="alert alert-info py-2 mt-2 mb-0">
                  An SMTP app password is already configured for this account.
                </div>
              @endif
            </div>

            <div class="col-md-6 mb-4">
              <label class="form-label">Default CC Recipients</label>
              <div class="form-text mb-2">
                Select users to automatically CC on Rider-module emails sent by you.
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
                      {{ $checked ? 'checked' : '' }}
                    >
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
              Save Email Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

