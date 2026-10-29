@extends('layouts.settingsPanelLayout')

@section('title', 'Voucher Approval Permissions')

@section('content')
@php
    $companySlug = $settingsCompanySlug ?? \App\Support\CompanyRouteContext::slug();
@endphp
<div class="container-fluid flex-grow-1 container-p-y">
    <h4 class="mb-1">Voucher Approval Permissions</h4>
    <p class="text-muted">Approve and reject rights are assigned through the existing Role Permissions matrix (Spatie). Assign per voucher type (and company via the role’s company). Changes are logged in the approval audit trail.</p>

    <div class="alert alert-info">
        Open <a href="{{ route('settings-panel.roles.index', ['company_slug' => $companySlug]) }}">Settings → Roles</a>,
        edit a role, then use the separate <strong>Voucher Approval Permissions</strong> card
        (Approve / Reject per voucher type, plus override and dashboard toggles).
        That card is not mixed into Module Permissions.
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Global / override permissions</strong></div>
        <ul class="list-group list-group-flush">
            @foreach($globals as $key => $name)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ str_replace('_', ' ', $key) }}</span>
                    <code>{{ $name }}</code>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card">
        <div class="card-header"><strong>Per voucher type</strong></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Label</th>
                        <th>Module</th>
                        <th>Approve permission</th>
                        <th>Reject permission</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($types as $code => $def)
                        <tr>
                            <td>{{ $code }}</td>
                            <td>{{ $def['label'] ?? $code }}</td>
                            <td>{{ $def['module'] ?? '—' }}</td>
                            <td><code>{{ $def['approve'] ?? '—' }}</code></td>
                            <td><code>{{ $def['reject'] ?? '—' }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
