<!-- resources/views/suppliers/view.blade.php -->
@extends('layouts.app')
@section('title', 'Supplier Profile')

@section('content')
<div class="row">

    
    <div class="col-xl-12 col-md-9 col-lg-7 order-0 order-md-1">
        <div class="nav-align-top">
          <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-3 row-gap-2">
            <li class="nav-item"><a class="nav-link @if(Route::is('suppliers.show')) active @endif" href="@isset($supplier->id){{ route('suppliers.show', $supplier->id) }}@else#@endif"><i class="ti ti-user-check ti-sm me-1_5"></i>Information</a></li>
            @can('suppliers_ledger_view')
            <li class="nav-item"><a class="nav-link @if(Route::is('suppliers.ledger')) active @endif" href="{{ route('suppliers.ledger', $supplier->id) }}"><i class="ti ti-file ti-sm me-1_5"></i>Ledger</a></li>
            @endcan
            @can('suppliers_documents_view')
            <li class="nav-item"><a class="nav-link @if(Route::is('suppliers.document')) active @endif" href="{{ route('suppliers.document', $supplier->id) }}"><i class="ti ti-file ti-sm me-1_5"></i>Documents</a></li>
            @endcan
          </ul>
        </div>

        <div class="card mb-5" id="cardBody">
          @yield('page_content')
        </div>
    </div>
</div>
@endsection