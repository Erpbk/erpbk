@extends('layouts.app')
@section('title','Accounts')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Chart Of Accounts</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary float-right action-btn show-modal"
                    href="javascript:void(0);" data-action="{{ route('accounts.create') }}" data-size="lg" data-title="New Account">
                    Add New
                </a>
            </div>
        </div>
    </div>
</section>
{{-- {{ $settings['company_name'] ?? 'Default Site Name' }} --}}
<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card py-3">
        <div class="container">
            <ul id="accounts-tree">
                @php
                $maincat = App\Helpers\Accounts::AccountTypes();
                @endphp
                @foreach ($maincat as $key => $account)
                @php
                $accounts = App\Models\Accounts::where('account_type', $account)->where('parent_id', null)->get();
                @endphp
                <li style="list-style:none; cursor: pointer;border-bottom:1px solid #efefef;" class="text-primary py-2">
                    <span class="toggle-btn plus-box" style="">+</span> {{ $key }} <span class="text-muted "><small>({{ $account }})</small></span>
                    <span style="float:right;">
                    </span>
                    @if($accounts->count() > 0)
                    <ul class="nested d-none">
                        @foreach ($accounts as $account)
                        @include('accounts.account-node', ['account' => $account])
                        @endforeach
                    </ul>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
@section('page-script')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.toggle-btn').forEach(button => {
            button.addEventListener('click', function() {
                let childList = this.parentElement.querySelector('.nested');
                if (childList) {
                    childList.classList.toggle('d-none'); // Show/hide child elements
                    this.textContent = childList.classList.contains('d-none') ? '+' : '-';
                }
            });
        });

        document.querySelectorAll('.lock-toggle').forEach(lock => {
            lock.addEventListener('click', function(e) {
                e.stopPropagation();
                const accountId = this.getAttribute('data-account-id');
                const lockBtn = this;
                fetch(`/accounts/accounts/${accountId}/toggle-lock`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const icon = lockBtn.querySelector('i');
                            icon.className = `fas ${data.icon} ${data.icon_class}`;
                            lockBtn.setAttribute('title', data.title);

                            // ✅ Update only current account buttons (not children)
                            const li = lockBtn.closest('li[data-account-id]');
                            if (!li) return;

                            const buttonWrapper = li.querySelector('span[style*="float:right"]');
                            if (!buttonWrapper) return;

                            const deleteBtns = buttonWrapper.querySelectorAll('.delete-btn');
                            const editBtns = buttonWrapper.querySelectorAll('.edit-btn');

                            if (data.is_locked) {
                                // LOCK: Disable only this account’s buttons
                                editBtns.forEach(btn => {
                                    btn.classList.add('locked-btn');
                                    btn.setAttribute('disabled', 'disabled');
                                    if (!btn.dataset.originalAction && btn.getAttribute("data-action")) {
                                        btn.dataset.originalAction = btn.getAttribute("data-action");
                                    }
                                    btn.removeAttribute("data-action");
                                    btn.classList.remove('show-modal');
                                });
                                deleteBtns.forEach(btn => {
                                    btn.classList.add('locked-btn');
                                    btn.setAttribute('disabled', 'disabled');
                                });
                            } else {
                                // UNLOCK: Restore only this account’s buttons
                                editBtns.forEach(btn => {
                                    btn.classList.remove('locked-btn');
                                    btn.removeAttribute('disabled');
                                    if (btn.dataset.originalAction) {
                                        btn.setAttribute("data-action", btn.dataset.originalAction);
                                    }
                                    btn.classList.add('show-modal');
                                });
                                deleteBtns.forEach(btn => {
                                    btn.classList.remove('locked-btn');
                                    btn.removeAttribute('disabled');
                                });
                            }
                        }
                    });
            });
        });

        // Prevent clicks on disabled edit buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-btn[disabled]')) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });
</script>
@endsection