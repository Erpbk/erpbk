@extends($layout ?? 'layouts.app')

@section('title', 'Send WhatsApp')

@section('content')
@php
$companySlug = request()->route('company_slug') ?? session('company_slug');
@endphp
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Send WhatsApp</h1>
            </div>
            <div class="col-sm-6 text-end">
                @if(auth()->user() && method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin())
                <a href="{{ route('settings-panel.whatsapp-settings.index', ['company_slug' => $companySlug]) }}" class="btn btn-outline-secondary btn-sm">
                    WhatsApp Settings
                </a>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    @include('adminlte-templates::common.errors')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <p class="text-muted mb-2">
                Free-form text works only inside the 24-hour customer care window (after the recipient messages you).
                Use an approved Meta template to start a conversation.
            </p>
            @if(!empty($openWindows))
                <div class="small">
                    <strong>Open 24h windows:</strong>
                    {{ implode(', ', $openWindows) }}
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($whatsappConnections->isEmpty())
                <div class="alert alert-warning mb-0">
                    @if(($companyConnectionCount ?? 0) > 0)
                        A WhatsApp number exists for this company, but none are allotted to your user.
                        Ask a company admin to allot you a number under <strong>WhatsApp Settings</strong>.
                    @else
                        No active WhatsApp numbers are available for this company.
                        Ask an app administrator to assign a number to the company, then have a company admin allot it to you in WhatsApp Settings.
                    @endif
                </div>
            @else
            @if(empty($whatsappChannelEnabled))
                <div class="alert alert-info">
                    WhatsApp is not enabled in this company’s notification channels yet.
                    Manual sends still work; enable the channel in Admin → Companies if you also want automated WhatsApp notifications.
                </div>
            @endif
            <form method="POST" action="{{ route('settings-panel.whatsapp-compose.send', ['company_slug' => $companySlug]) }}" id="whatsappComposeForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required" for="whatsapp_connection_id">Connection</label>
                        <select name="whatsapp_connection_id" id="whatsapp_connection_id"
                            class="form-select no-select2 @error('whatsapp_connection_id') is-invalid @enderror"
                            data-no-select2
                            required>
                            @foreach($whatsappConnections as $connection)
                                <option value="{{ $connection->id }}" @selected((int) old('whatsapp_connection_id') === (int) $connection->id)>
                                    {{ $connection->displayLabel() }}
                                </option>
                            @endforeach
                        </select>
                        @error('whatsapp_connection_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="to_phone">To phone</label>
                        <input type="text" name="to_phone" id="to_phone" class="form-control @error('to_phone') is-invalid @enderror"
                               value="{{ old('to_phone') }}" required maxlength="40" placeholder="+9715…">
                        @error('to_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="mode">Mode</label>
                        <select name="mode" id="mode"
                            class="form-select no-select2 @error('mode') is-invalid @enderror"
                            data-no-select2
                            required>
                            <option value="text" @selected(old('mode', 'text') === 'text')>Free-form text</option>
                            <option value="template" @selected(old('mode') === 'template')>Template</option>
                        </select>
                        @error('mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 js-compose-text">
                        <label class="form-label required" for="body">Message</label>
                        <textarea name="body" id="body" rows="5" class="form-control @error('body') is-invalid @enderror"
                                  maxlength="4096">{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 js-compose-template d-none">
                        <label class="form-label required" for="template_name">Template name</label>
                        <input type="text" name="template_name" id="template_name" class="form-control @error('template_name') is-invalid @enderror"
                               value="{{ old('template_name') }}" maxlength="128">
                        @error('template_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 js-compose-template d-none">
                        <label class="form-label" for="template_language">Language</label>
                        <input type="text" name="template_language" id="template_language" class="form-control"
                               value="{{ old('template_language', 'en') }}" maxlength="16">
                    </div>
                    <div class="col-md-3 js-compose-template d-none">
                        <label class="form-label" for="template_params">Body params</label>
                        <textarea name="template_params" id="template_params" rows="3" class="form-control"
                                  placeholder="One param per line">{{ old('template_params') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i> Send
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
