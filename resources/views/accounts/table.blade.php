@php
    $accounts = $accounts ?? collect();
@endphp
<table class="table table-hover table-striped mb-0 chart-table">
    <thead class="table-light">
        <tr>
            <th class="account-name-cell ps-3">Account Name</th>
            <th class="text-nowrap">Account Code</th>
            <th class="text-nowrap">Account Type</th>
            <th class="text-nowrap">Documents</th>
            <th class="text-nowrap">Parent Account</th>
            <th style="width: 80px;" class="text-end pe-3">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($accounts as $row)
            @php
                $account = $row->account;
                $depth = $row->depth ?? 0;
                $hasChildren = $account->relationLoaded('children') ? $account->children->isNotEmpty() : $account->children()->exists();
            @endphp
            <tr data-id="{{ $account->id }}">
                <td class="account-name-cell ps-3">
                    <div class="account-name-wrap">
                        @for ($i = 0; $i < $depth; $i++)
                            <span class="indent indent-lines"></span>
                        @endfor
                        @if ($depth > 0)
                            <span class="indent"></span>
                        @endif
                        @if ($hasChildren)
                            <input type="checkbox" class="form-check-input account-checkbox me-2" value="{{ $account->id }}" aria-label="Select account">
                        @else
                            <span class="me-2" style="width: 1.25rem; display: inline-block;"></span>
                        @endif
                        @if ($account->is_locked ?? false)
                            <i class="fa fa-lock text-muted small" title="Locked" aria-hidden="true"></i>
                        @endif
                        @can('account_view')
                        <a href="{{ route('accounts.show', $account->id) }}">{{ $account->name }}</a>
                        @else
                        <span>{{ $account->name }}</span>
                        @endcan
                    </div>
                </td>
                <td class="text-nowrap">{{ $account->account_code ?? '—' }}</td>
                <td class="text-nowrap">{{ $account->account_type ?? '—' }}</td>
                <td class="text-muted">—</td>
                <td class="text-nowrap text-muted">{{ $account->parent->name ?? '—' }}</td>
                <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary btn-actions dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                            <i class="fa fa-cog"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @can('account_edit')
                            <li>
                                <a class="dropdown-item show-modal" href="javascript:void(0);" data-action="{{ route('accounts.edit', $account->id) }}" data-size="lg" data-title="Edit Account">
                                    <i class="fa fa-edit me-2"></i> Edit
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item toggle-lock" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.toggleLock', $account->id) }}">
                                    <i class="fa fa-{{ $account->is_locked ? 'unlock' : 'lock' }} me-2"></i> {{ $account->is_locked ? 'Unlock' : 'Lock' }}
                                </a>
                            </li>
                            @endcan
                            @can('account_delete')
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger delete-account" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.destroy', $account->id) }}">
                                    <i class="fa fa-trash me-2"></i> Delete
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">No accounts found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@push('third_party_scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.delete-account', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                        success: function(res) {
                            if (res.message) {
                                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Done', html: res.message });
                                setTimeout(function() { location.reload(); }, 1500);
                            }
                        },
                        error: function(xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.error) ? xhr.responseJSON.errors.error : 'Could not delete account.';
                            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: msg });
                        }
                    });
                }
            });
        } else if (confirm('Are you sure you want to delete this account?')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        }
    });

    $(document).on('click', '.toggle-lock', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        var $row = $(this).closest('tr');
        $.post(url, { _token: '{{ csrf_token() }}' })
            .done(function(res) {
                if (res.success && res.icon) {
                    var $link = $row.find('.toggle-lock');
                    $link.find('i').attr('class', 'fa ' + res.icon + ' me-2');
                    $link.text(res.is_locked ? ' Unlock' : ' Lock');
                    var $nameCell = $row.find('.account-name-wrap');
                    var $lockIcon = $nameCell.find('.fa-lock');
                    if (res.is_locked && !$lockIcon.length) {
                        $nameCell.find('.account-checkbox').first().after('<i class="fa fa-lock text-muted small" title="Locked"></i>');
                    } else if (!res.is_locked) {
                        $nameCell.find('.fa-lock').remove();
                    }
                }
            });
    });
});
</script>
@endpush
