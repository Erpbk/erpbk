@extends('layouts.app')
@section('title', __('Edit Global Account'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('admin.global-accounts.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Back to list') }}</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.global-accounts.update', $globalAccount) }}" id="globalAccountForm">
                @csrf
                @method('PUT')
                @include('admin.global_accounts._form', ['globalAccount' => $globalAccount])
                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                <a href="{{ route('admin.global-accounts.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
@include('admin.global_accounts._form_script')
@endsection
