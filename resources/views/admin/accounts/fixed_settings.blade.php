@extends('layouts.app')
@section('title', __('Account Fixing'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('Account Fixing') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Mark chart accounts as fixed so they are available to all companies.') }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                <form method="GET" action="{{ route('admin.accounts.fixed.index') }}" class="row g-2">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, code, or type" value="{{ $search }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                    </div>
                </form>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createFixedAccountModal">
                    {{ __('Add Fixed Account') }}
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Company ID') }}</th>
                            <th>{{ __('Fixed') }}</th>
                            <th class="text-end">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        @php
                        $childCount = \App\Models\Accounts::withoutGlobalScopes(['company', 'branch'])->where('parent_id', $account->id)->count();
                        @endphp
                        <tr>
                            <td>{{ $account->id }}</td>
                            <td>{{ $account->account_code ?? '—' }}</td>
                            <td>{{ $account->name }}</td>
                            <td>{{ $account->account_type ?? '—' }}</td>
                            <td>{{ $account->company_id ?? '—' }}</td>
                            <td>
                                @if($account->is_fixed)
                                <span class="badge bg-success">{{ __('Yes') }}</span>
                                @else
                                <span class="badge bg-secondary">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.accounts.fixed.toggle', ['account' => $account->id]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $account->is_fixed ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                        {{ $account->is_fixed ? __('Unmark Fixed') : __('Mark Fixed') }}
                                    </button>
                                </form>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary open-edit-modal"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editFixedAccountModal"
                                    data-id="{{ $account->id }}"
                                    data-name="{{ $account->name }}"
                                    data-type="{{ $account->account_type }}"
                                    data-parent="{{ $account->parent_id }}"
                                    data-opening-balance="{{ $account->opening_balance }}"
                                    data-status="{{ $account->status }}"
                                    data-notes="{{ $account->notes }}"
                                    data-is-fixed="{{ $account->is_fixed ? 1 : 0 }}"
                                    data-child-count="{{ $childCount }}">
                                    {{ __('Edit') }}
                                </button>
                                @if($account->is_fixed)
                                <form method="POST" action="{{ route('admin.accounts.fixed.destroy', ['account' => $account->id]) }}" class="d-inline" onsubmit="return confirm('Delete this fixed account? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">{{ __('No accounts found.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $accounts->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createFixedAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.accounts.fixed.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Fixed Account') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Account Name') }}</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Account Type') }}</label>
                            <select name="account_type" class="form-control form-select modal-select" required>
                                <option value="">{{ __('Select') }}</option>
                                @foreach($accountTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Parent Account') }}</label>
                            <select name="parent_id" class="form-control form-select modal-select">
                                <option value="">{{ __('Select') }}</option>
                                @foreach($parents as $parent)
                                <option value="{{ $parent->id }}">{{ ($parent->account_code ? $parent->account_code . ' - ' : '') . $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Opening Balance') }}</label>
                            <input type="number" step="any" name="opening_balance" class="form-control" value="0">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-control form-select modal-select">
                                <option value="1">{{ __('Active') }}</option>
                                <option value="2">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Fixed Account') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editFixedAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editFixedAccountForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Account') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-none" id="editFixedAccountRestriction"></div>
                    <div class="row g-3">
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Account Name') }}</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Account Type') }}</label>
                            <select name="account_type" id="edit_account_type" class="form-control form-select modal-select" required>
                                <option value="">{{ __('Select') }}</option>
                                @foreach($accountTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Parent Account') }}</label>
                            <select name="parent_id" id="edit_parent_id" class="form-control form-select modal-select">
                                <option value="">{{ __('Select') }}</option>
                                @foreach($parents as $parent)
                                <option value="{{ $parent->id }}">{{ ($parent->account_code ? $parent->account_code . ' - ' : '') . $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Opening Balance') }}</label>
                            <input type="number" step="any" name="opening_balance" id="edit_opening_balance" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" id="edit_status" class="form-control form-select modal-select">
                                <option value="1">{{ __('Active') }}</option>
                                <option value="2">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" id="edit_notes" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="editSaveBtn">{{ __('Update Account') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('third_party_scripts')
<script>
    var fixedAccountUpdateUrlTemplate = "{{ route('admin.accounts.fixed.update', ['account' => '__ID__']) }}";
    function setSelectValue(selectId, value) {
        var element = document.getElementById(selectId);
        if (!element) return;
        element.value = value || '';
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(element).trigger('change.select2');
        }
    }

    document.addEventListener('shown.bs.modal', function(event) {
        if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2)) return;
        var modal = event.target;
        window.jQuery(modal).find('.modal-select').each(function() {
            var $el = window.jQuery(this);
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            $el.select2({
                dropdownParent: window.jQuery(modal),
                width: '100%'
            });
        });
    });

    document.addEventListener('click', function(event) {
        var trigger = event.target.closest('.open-edit-modal');
        if (!trigger) return;

        var id = trigger.getAttribute('data-id');
        var isFixed = trigger.getAttribute('data-is-fixed') === '1';
        var childCount = parseInt(trigger.getAttribute('data-child-count') || '0', 10);

        var form = document.getElementById('editFixedAccountForm');
        form.setAttribute('action', fixedAccountUpdateUrlTemplate.replace('__ID__', id));

        document.getElementById('edit_name').value = trigger.getAttribute('data-name') || '';
        setSelectValue('edit_account_type', trigger.getAttribute('data-type') || '');
        setSelectValue('edit_parent_id', trigger.getAttribute('data-parent') || '');
        document.getElementById('edit_opening_balance').value = trigger.getAttribute('data-opening-balance') || '0';
        setSelectValue('edit_status', trigger.getAttribute('data-status') || '1');
        document.getElementById('edit_notes').value = trigger.getAttribute('data-notes') || '';

        var restriction = document.getElementById('editFixedAccountRestriction');
        var saveBtn = document.getElementById('editSaveBtn');
        if (isFixed && childCount > 0) {
            restriction.textContent = 'This fixed account cannot be edited because it has child accounts.';
            restriction.classList.remove('d-none');
            saveBtn.disabled = true;
        } else {
            restriction.classList.add('d-none');
            restriction.textContent = '';
            saveBtn.disabled = false;
        }
    });
</script>
@endpush