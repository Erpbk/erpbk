@extends('layouts.settingsPanelLayout')

@section('title', 'WhatsApp Settings')

@section('content')
@php
  $companySlug = request()->route('company_slug') ?? session('company_slug');
  $userLabel = function ($u) {
      $displayEmail = $u->email ?: $u->username;
      $displayName = $u->name ?: trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
      $label = trim($displayName) !== '' ? $displayName : 'User';
      if ($displayEmail) {
          $label .= ' ('.$displayEmail.')';
      }
      return $label;
  };
@endphp
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1>WhatsApp Settings</h1></div>
    </div>
  </div>
</section>

<div class="content px-3">
  @include('flash::message')

  <form method="post" action="{{ route('settings-panel.whatsapp-settings.update', ['company_slug' => $companySlug]) }}">
    @csrf

    <div class="card mb-3">
      <div class="card-header"><strong>Numbers &amp; user allotment</strong></div>
      <div class="card-body">
        <p class="text-muted mb-2">
          Numbers are assigned to your company by the app administrator.
          Assign those numbers to your users here. Users with no allotted number cannot send WhatsApp (including admins).
        </p>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr><th style="min-width:220px">Number</th><th>Assigned users</th></tr>
            </thead>
            <tbody>
              @forelse($numbers as $num)
                @php
                  $assignedUserIds = $num->relationLoaded('users')
                    ? $num->users->pluck('id')->map(fn ($id) => (int) $id)->all()
                    : [];
                @endphp
                <tr>
                  <td>{{ method_exists($num, 'displayLabel') ? $num->displayLabel() : ($num->label ?: $num->display_phone_number) }}</td>
                  <td>
                    <select
                      name="allotments[{{ $num->id }}][]"
                      class="form-select"
                      multiple
                      data-placeholder="Select users who may send from this number"
                      style="width:100%">
                      @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(in_array((int) $u->id, $assignedUserIds, true))>
                          {{ $userLabel($u) }}
                        </option>
                      @endforeach
                    </select>
                    @if($assignedUserIds === [])
                      <div class="form-text text-danger">Nobody allotted — send is disabled for this number.</div>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-muted">
                    No WhatsApp numbers are assigned to this company yet. Contact FleetValu support to assign numbers.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><strong>Unknown inbound Messages</strong></div>
      <div class="card-body">
        <p class="text-muted">When a new unknown number messages a company WhatsApp number, route it to this user.</p>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr><th>Number</th><th>Default Receiver</th></tr>
            </thead>
            <tbody>
              @forelse($numbers as $num)
                @php $current = optional($settings->get($num->id))->user_id; @endphp
                <tr>
                  <td>{{ method_exists($num, 'displayLabel') ? $num->displayLabel() : ($num->label ?: $num->display_phone_number) }}</td>
                  <td>
                    <select name="assignees[{{ $num->id }}]" class="form-select form-select-sm">
                      <option value="">— Admins only —</option>
                      @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((int) $current === (int) $u->id)>{{ $userLabel($u) }}</option>
                      @endforeach
                    </select>
                  </td>
                </tr>
              @empty
                <tr><td colspan="2" class="text-muted">No assigned WhatsApp numbers.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    @if($numbers->isNotEmpty())
      <button type="submit" class="btn btn-primary mb-4">Save WhatsApp settings</button>
    @endif
  </form>
</div>
@endsection
