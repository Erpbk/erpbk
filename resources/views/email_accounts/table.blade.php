@php
$accountRoute = $accountRoute ?? 'settings-panel.email-accounts';
$embeddedEmailAccountManager = $embeddedEmailAccountManager ?? false;
$companySlug = request()->route('company_slug') ?? session('company_slug');
@endphp
<div class="table-responsive">
    <table class="table table-striped" id="email-accounts-table">
        <thead>
            <tr>
                <th>Email Address</th>
                <th>Display Name</th>
                <th>Status</th>
                <th>Assigned Users</th>
                <th>Created</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accounts as $account)
            @php
            $assignedUserIds = $account->relationLoaded('users')
                ? $account->users->pluck('id')->all()
                : [];
            @endphp
            <tr data-id="{{ $account->id }}">
                <td>{{ $account->email }}</td>
                <td>{{ $account->display_name ?: '—' }}</td>
                <td>
                    <span class="badge bg-{{ $account->isActive() ? 'success' : 'secondary' }}">
                        {{ $account->isActive() ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>{{ (int) ($account->users_count ?? count($assignedUserIds)) }}</td>
                <td>{{ $account->created_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $account->updated_at?->format('Y-m-d H:i') }}</td>
                <td>
                    <div class="btn-group">
                        @can('update', $account)
                        @if($embeddedEmailAccountManager)
                        <button type="button"
                            class="btn btn-sm btn-primary js-email-account-edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editEmailAccountModal"
                            data-id="{{ $account->id }}"
                            data-email="{{ $account->email }}"
                            data-display-name="{{ $account->display_name }}"
                            data-status="{{ $account->status }}"
                            data-user-ids="{{ json_encode($assignedUserIds) }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        @else
                        <a href="{{ route($accountRoute . '.edit', ['company_slug' => $companySlug, 'email_account' => $account->id]) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                        @endcan
                        @can('delete', $account)
                        <button type="button" class="btn btn-sm btn-danger js-email-account-delete-btn" data-delete-form-id="delete-email-account-{{ $account->id }}">
                            <i class="fas fa-trash"></i>
                        </button>
                        <form id="delete-email-account-{{ $account->id }}" action="{{ route($accountRoute . '.destroy', ['company_slug' => $companySlug, 'email_account' => $account->id]) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">No email accounts configured yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
