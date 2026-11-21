@php
$template = $template ?? null;
$injectionHints = $injectionHints ?? [];
$injection = $injection ?? null;
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="label">{{ __('Label') }}</label>
        <input type="text" name="label" id="label" class="form-control @error('label') is-invalid @enderror"
               value="{{ old('label', $template->label ?? '') }}" maxlength="255">
        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label required" for="name">{{ __('Meta template name') }}</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $template->name ?? '') }}" required maxlength="128"
               placeholder="absent_notification">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('Must match Meta exactly. Variable injections are defined in config/whatsapp_templates.php.') }}</div>
    </div>
    <div class="col-md-3">
        <label class="form-label required" for="language">{{ __('Language') }}</label>
        <input type="text" name="language" id="language" class="form-control"
               value="{{ old('language', $template->language ?? 'en') }}" required maxlength="16">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="category">{{ __('Category') }}</label>
        <select name="category" id="category" class="form-select no-select2" data-no-select2>
            @php $cat = old('category', $template->category ?? ''); @endphp
            <option value="">{{ __('—') }}</option>
            <option value="utility" @selected($cat === 'utility')>{{ __('Utility') }}</option>
            <option value="marketing" @selected($cat === 'marketing')>{{ __('Marketing') }}</option>
            <option value="authentication" @selected($cat === 'authentication')>{{ __('Authentication') }}</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label required" for="param_style">{{ __('Parameter style') }}</label>
        <select name="param_style" id="param_style" class="form-select no-select2" data-no-select2 required>
            <option value="positional" @selected(old('param_style', $template->param_style ?? '') === 'positional')>{{ __('Positional') }}</option>
            <option value="named" @selected(old('param_style', $template->param_style ?? 'named') === 'named')>{{ __('Named') }}</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label required" for="status">{{ __('Status') }}</label>
        <select name="status" id="status" class="form-select no-select2" data-no-select2 required>
            <option value="active" @selected(old('status', $template->status ?? 'active') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected(old('status', $template->status ?? '') === 'inactive')>{{ __('Inactive') }}</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="body_preview">{{ __('Body preview') }}</label>
        <textarea name="body_preview" id="body_preview" rows="3" class="form-control" maxlength="2000">{{ old('body_preview', $template->body_preview ?? '') }}</textarea>
    </div>

    @if($injection)
        <div class="col-12">
            <div class="alert alert-success mb-0">
                <strong>{{ __('Config injection found') }}</strong>
                <div class="small mt-1">
                    @foreach(($injection['variables'] ?? []) as $var)
                        <code>{{ $var['parameter_name'] ?? '—' }}</code> → <code>{{ $var['inject'] ?? '—' }}</code>
                        @if(!$loop->last)·@endif
                    @endforeach
                </div>
            </div>
        </div>
    @elseif(!empty($template?->name))
        <div class="col-12">
            <div class="alert alert-warning mb-0">
                {{ __('No injection config for this Meta name yet. Add it under config/whatsapp_templates.php before sending.') }}
            </div>
        </div>
    @endif

    @if(!empty($injectionHints))
        <div class="col-12">
            <div class="form-text">
                {{ __('Configured injections:') }}
                @foreach($injectionHints as $n => $hint)
                    <div><code>{{ $n }}</code>: {{ $hint }}</div>
                @endforeach
            </div>
        </div>
    @endif
</div>
