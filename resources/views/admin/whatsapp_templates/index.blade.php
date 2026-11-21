@extends('layouts.app')
@section('title', __('WhatsApp Templates'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('WhatsApp Templates') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Register Meta templates and bind them to notification / auto-send triggers. Variable injections live in config.') }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex gap-2 mb-3 justify-content-between flex-wrap">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-{{ !request('status') ? 'primary' : 'outline-primary' }} btn-sm">{{ __('All') }}</a>
                    <a href="{{ route('admin.whatsapp-templates.index', ['status' => 'active']) }}" class="btn btn-{{ request('status') === 'active' ? 'success' : 'outline-success' }} btn-sm">{{ __('Active') }}</a>
                </div>
                <a href="{{ route('admin.whatsapp-templates.create') }}" class="btn btn-primary btn-sm">{{ __('Add Template') }}</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Label') }}</th>
                            <th>{{ __('Meta name') }}</th>
                            <th>{{ __('Language') }}</th>
                            <th>{{ __('Params') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>{{ $template->label ?: '—' }}</td>
                                <td><code>{{ $template->name }}</code></td>
                                <td>{{ $template->language }}</td>
                                <td>{{ $template->param_style === 'named' ? __('Named') : __('Positional') }}</td>
                                <td>
                                    @if($template->isActive())
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.whatsapp-templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    <form action="{{ route('admin.whatsapp-templates.destroy', $template) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm(@json(__('Delete this template?')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('No WhatsApp templates yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $templates->links() }}
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Trigger bindings') }}</h5>
            <p class="text-muted small mb-0">{{ __('Messages send only when a template is bound to the trigger. Injections must exist in config for that Meta name.') }}</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.whatsapp-templates.bindings.update') }}">
                @csrf
                @method('PUT')
                @php $allTemplates = \App\Models\WhatsAppTemplate::query()->active()->orderBy('name')->get(); @endphp
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Trigger') }}</th>
                                <th>{{ __('Template') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bindingRows as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $row['label'] }}</div>
                                        <code class="small">{{ $row['trigger_key'] }}</code>
                                    </td>
                                    <td>
                                        <select name="bindings[{{ $row['trigger_key'] }}][template_id]"
                                                class="form-select form-select-sm no-select2" data-no-select2>
                                            <option value="">{{ __('— None (do not send) —') }}</option>
                                            @foreach($allTemplates as $tpl)
                                                <option value="{{ $tpl->id }}" @selected((int) ($row['template_id'] ?? 0) === (int) $tpl->id)>
                                                    {{ $tpl->displayLabel() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Save bindings') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
