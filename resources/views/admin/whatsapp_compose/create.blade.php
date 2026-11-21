@extends('layouts.app')
@section('title', __('Send WhatsApp'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm flex-grow-1 me-3">
                <h3 class="text-white mb-0 fw-bold">{{ __('Send WhatsApp') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Compose a message from any platform WhatsApp number') }}</p>
            </div>
            <a href="{{ route('admin.whatsapp-numbers.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('WhatsApp Numbers') }}</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <p class="text-muted mb-2">
                {{ __('Free-form text works only inside the 24-hour customer care window. Use an approved Meta template to start a conversation.') }}
            </p>
            @if(!empty($openWindows))
                <div class="small">
                    <strong>{{ __('Open 24h windows') }}:</strong>
                    {{ implode(', ', $openWindows) }}
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($whatsappNumbers->isEmpty())
                <div class="alert alert-warning mb-0">
                    {{ __('No active WhatsApp numbers available.') }}
                    <a href="{{ route('admin.whatsapp-numbers.create') }}">{{ __('Add a number') }}</a> {{ __('first.') }}
                </div>
            @else
            <form method="POST" action="{{ route('admin.whatsapp-compose.send') }}" id="whatsappComposeForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required" for="whatsapp_connection_id">{{ __('WhatsApp Number') }}</label>
                        <select name="whatsapp_connection_id" id="whatsapp_connection_id"
                            class="form-select no-select2 @error('whatsapp_connection_id') is-invalid @enderror"
                            data-no-select2 required>
                            @foreach($whatsappNumbers as $number)
                                <option value="{{ $number->id }}" @selected((int) old('whatsapp_connection_id') === (int) $number->id)>
                                    {{ $number->displayLabel() }}
                                    @if($number->company) — {{ $number->company->name }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('whatsapp_connection_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="to_phone">{{ __('To phone') }}</label>
                        <input type="text" name="to_phone" id="to_phone" class="form-control @error('to_phone') is-invalid @enderror"
                               value="{{ old('to_phone') }}" required maxlength="40" placeholder="+9715…">
                        @error('to_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="mode">{{ __('Mode') }}</label>
                        <select name="mode" id="mode"
                            class="form-select no-select2 @error('mode') is-invalid @enderror"
                            data-no-select2 required>
                            <option value="text" @selected(old('mode', 'text') === 'text')>{{ __('Free-form text') }}</option>
                            <option value="template" @selected(old('mode') === 'template')>{{ __('Template') }}</option>
                        </select>
                        @error('mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 js-compose-text">
                        <label class="form-label required" for="body">{{ __('Message') }}</label>
                        <textarea name="body" id="body" rows="5" class="form-control @error('body') is-invalid @enderror"
                                  maxlength="4096">{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 js-compose-template d-none">
                        <label class="form-label required" for="template_name">{{ __('Template name') }}</label>
                        <input type="text" name="template_name" id="template_name" class="form-control @error('template_name') is-invalid @enderror"
                               value="{{ old('template_name') }}" maxlength="128">
                        @error('template_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 js-compose-template d-none">
                        <label class="form-label" for="template_language">{{ __('Language') }}</label>
                        <input type="text" name="template_language" id="template_language" class="form-control"
                               value="{{ old('template_language', 'en') }}" maxlength="16">
                    </div>
                    <div class="col-md-3 js-compose-template d-none">
                        <label class="form-label" for="template_params">{{ __('Body params') }}</label>
                        <textarea name="template_params" id="template_params" rows="3" class="form-control"
                                  placeholder="{{ __('One param per line') }}">{{ old('template_params') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i> {{ __('Send') }}
                        </button>
                    </div>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('page-scripts')
<script>
(function () {
    const mode = document.getElementById('mode');
    if (!mode) return;
    function sync() {
        const isTemplate = mode.value === 'template';
        document.querySelectorAll('.js-compose-text').forEach(el => el.classList.toggle('d-none', isTemplate));
        document.querySelectorAll('.js-compose-template').forEach(el => el.classList.toggle('d-none', !isTemplate));
    }
    mode.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
