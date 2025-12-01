{{-- riders/tabs.blade.php --}}
@php
  $currentRoute = Route::currentRouteName();
@endphp

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->is("riders/$rider->id") ? 'active' : '' }}" href="{{ route('riders.show', $rider->id) }}">Overview</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is("riders/$rider->id/document") ? 'active' : '' }}" href="{{ route('riders.document', $rider->id) }}">Documents</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is("riders/$rider->id/attendance") ? 'active' : '' }}" href="{{ route('riders.attendance', $rider->id) }}">Attendance</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is("riders/$rider->id/ledger") ? 'active' : '' }}" href="{{ route('riders.ledger', $rider->id) }}">Ledger</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is("riders/$rider->id/invoices") ? 'active' : '' }}" href="{{ route('riders.invoices', $rider->id) }}">Invoices</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is("riders/$rider->id/activities") ? 'active' : '' }}" href="{{ route('riders.activities', $rider->id) }}">Activities</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is("riders/$rider->id/timeline") ? 'active' : '' }}" href="{{ route('riders.timeline', $rider->id) }}">Timeline</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->is("riders/$rider->id/contract") ? 'active' : '' }}" href="{{ route('riders.contract', $rider->id) }}">Contract</a>
    </li>
</ul>
