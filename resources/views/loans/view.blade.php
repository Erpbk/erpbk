@php
$statusColor = match($loan->status) {
    'active' => 'success',
    'closed' => 'secondary',
    'defaulted' => 'danger',
    default => 'warning',
};
@endphp

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <ul class="nav nav-pills nav-justified gap-2" role="tablist">
            <li class="nav-item">
                <a class="nav-link @if(Route::is('loans.show')) active @endif" href="{{ route('loans.show', $loan->id) }}">
                    <i class="ti ti-file-description me-1"></i> Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(Route::is('loans.installments')) active @endif" href="{{ route('loans.installments', $loan->id) }}">
                    <i class="ti ti-calendar me-1"></i> Schedule
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(Route::is('loan.files')) active @endif" href="{{ route('loan.files', $loan->id) }}">
                    <i class="ti ti-upload me-1"></i> Documents
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(Route::is('loans.ledger')) active @endif @if(!$loan->account_id) disabled @endif"
                   href="{{ $loan->account_id ? route('loans.ledger', $loan->id) : 'javascript:void(0);' }}">
                    <i class="ti ti-book me-1"></i> Ledger
                </a>
            </li>
        </ul>
    </div>
</div>
