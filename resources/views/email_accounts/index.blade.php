@extends($layout ?? 'layouts.app')

@section('title', 'Email Accounts')

@section('content')
@php
$accountRoute = $accountRoute ?? 'settings-panel.email-accounts';
$companySlug = request()->route('company_slug') ?? session('company_slug');
$returnTo = url()->current();
$companyUsers = \App\Models\User::query()
    ->when(($companyUsersCompanyId = \App\Support\CompanyContext::id()) !== null, fn($q) => $q->where('company_id', $companyUsersCompanyId))
    ->orderBy('name')
    ->get(['id', 'name', 'first_name', 'last_name', 'email', 'username']);
@endphp
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Email Accounts</h1>
            </div>
            <div class="col-sm-6">
                @can('create', \App\Models\EmailAccount::class)
                <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#createEmailAccountModal">
                    <i class="ti ti-plus me-1"></i> Add Email Account
                </button>
                @endcan
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    @include('adminlte-templates::common.errors')

    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-3">
                Configure company email accounts with Gmail app passwords. Assign accounts to users so they can send rider emails from the correct address.
            </p>
            <div id="emailAccountsTableWrapper">
                @include('email_accounts.table', [
                    'accounts' => $accounts,
                    'accountRoute' => $accountRoute,
                    'embeddedEmailAccountManager' => true,
                ])
            </div>
        </div>
    </div>
</div>

@can('create', \App\Models\EmailAccount::class)
<div class="modal fade" id="createEmailAccountModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route($accountRoute . '.store', ['company_slug' => $companySlug]) }}" id="createEmailAccountForm">
                @csrf
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                <div class="modal-header">
                    <h5 class="modal-title">Add Email Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('email_accounts.fields', [
                        'companyUsers' => $companyUsers,
                        'assignedUserIds' => old('user_ids', []),
                    ])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@can('create', \App\Models\EmailAccount::class)
<div class="modal fade" id="editEmailAccountModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="editEmailAccountForm" method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Email Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('email_accounts.fields', [
                        'companyUsers' => $companyUsers,
                        'assignedUserIds' => old('user_ids', []),
                        'isEdit' => true,
                    ])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<div id="email-account-manager-config"
    data-edit-url-template="{{ route($accountRoute . '.update', ['company_slug' => $companySlug, 'email_account' => '__ID__']) }}">
</div>
@endsection

@push('page-styles')
<style>
    #createEmailAccountModal .select2-container,
    #editEmailAccountModal .select2-container {
        width: 100% !important;
    }

    #createEmailAccountModal .select2-container--open .select2-dropdown,
    #editEmailAccountModal .select2-container--open .select2-dropdown {
        z-index: 2000;
    }
</style>
@endpush

@push('page-scripts')
<script>
(function ($) {
    function initEmailAccountSelect2(modalEl) {
        if (!modalEl || !$ || !$.fn.select2) {
            return;
        }

        const $modal = $(modalEl);
        const $parent = $modal.find('.modal-content').length ? $modal.find('.modal-content') : $modal;

        $modal.find('select.js-email-account-user-select').each(function () {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                width: '100%',
                placeholder: $select.data('placeholder') || 'Select users who may send from this account',
                allowClear: true,
                closeOnSelect: false,
                dropdownParent: $parent,
            });
        });
    }

    function destroyEmailAccountSelect2(modalEl) {
        if (!modalEl || !$ || !$.fn.select2) {
            return;
        }

        $(modalEl).find('select.js-email-account-user-select').each(function () {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
        });
    }

    $(function () {
        const config = document.getElementById('email-account-manager-config');
        const editUrlTemplate = config ? config.getAttribute('data-edit-url-template') : '';

        $('#createEmailAccountModal, #editEmailAccountModal')
            .on('shown.bs.modal', function () {
                initEmailAccountSelect2(this);
            })
            .on('hidden.bs.modal', function () {
                destroyEmailAccountSelect2(this);
            });

        $(document).on('click', '.js-email-account-delete-btn', function () {
            if (!confirm('Delete this email account?')) return;
            document.getElementById($(this).data('delete-form-id')).submit();
        });

        function fillEditForm(account) {
            const form = document.getElementById('editEmailAccountForm');
            if (!form || !account) return;

            form.action = editUrlTemplate.replace('__ID__', String(account.id || ''));
            form.querySelector('[name="email"]').value = account.email || '';
            form.querySelector('[name="display_name"]').value = account.display_name || '';
            form.querySelector('[name="status"]').value = account.status || 'active';
            form.querySelector('[name="app_password"]').value = '';

            const selected = (account.user_ids || []).map(String);
            const $userSelect = $(form).find('[name="user_ids[]"]');
            if ($userSelect.length) {
                $userSelect.val(selected).trigger('change');
            }
        }

        $(document).on('click', '.js-email-account-edit-btn', function () {
            const $btn = $(this);
            fillEditForm({
                id: $btn.data('id'),
                email: $btn.data('email'),
                display_name: $btn.data('displayName'),
                status: $btn.data('status'),
                user_ids: $btn.data('userIds') || [],
            });
        });

        @if($errors->any() && old('_method') === 'PUT')
        const editModalEl = document.getElementById('editEmailAccountModal');
        if (editModalEl && typeof bootstrap !== 'undefined') {
            const editId = @json(session('open_edit_account_id'));
            if (editId && editUrlTemplate) {
                document.getElementById('editEmailAccountForm').action = editUrlTemplate.replace('__ID__', String(editId));
            }
            bootstrap.Modal.getOrCreateInstance(editModalEl).show();
        }
        @elseif($errors->any())
        const createModalEl = document.getElementById('createEmailAccountModal');
        if (createModalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(createModalEl).show();
        }
        @endif

        @if(session('open_create_modal'))
        const openCreate = document.getElementById('createEmailAccountModal');
        if (openCreate && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(openCreate).show();
        }
        @endif

        @if(session('open_edit_account_id'))
        const openEdit = document.getElementById('editEmailAccountModal');
        const openEditId = @json(session('open_edit_account_id'));
        if (openEdit && openEditId && typeof bootstrap !== 'undefined') {
            const trigger = document.querySelector('.js-email-account-edit-btn[data-id="' + openEditId + '"]');
            if (trigger) trigger.click();
            else bootstrap.Modal.getOrCreateInstance(openEdit).show();
        }
        @endif
    });
})(window.jQuery);
</script>
@endpush
