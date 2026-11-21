@extends('layouts.app')
@section('title', __('WhatsApp Numbers'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('WhatsApp Numbers') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Manage platform WhatsApp Business numbers and assign them to companies') }}</p>
            </div>
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
                {{ __('Connect WhatsApp Business phones via Meta Cloud API. Assign numbers to companies (multiple per company allowed), or mark one as the platform default.') }}
            </p>
            <p class="mb-0 small">
                <strong>{{ __('Webhook URL') }}:</strong>
                <code>{{ $webhookUrl }}</code>
                — {{ __('subscribe to messages in Meta and use META_WHATSAPP_VERIFY_TOKEN.') }}
            </p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex gap-2 mb-3 justify-content-between flex-wrap">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.whatsapp-numbers.index') }}" class="btn btn-{{ !request('status') && !request('assignment') ? 'primary' : 'outline-primary' }} btn-sm">{{ __('All') }}</a>
                    <a href="{{ route('admin.whatsapp-numbers.index', ['status' => 'active']) }}" class="btn btn-{{ request('status') === 'active' ? 'success' : 'outline-success' }} btn-sm">{{ __('Active') }}</a>
                    <a href="{{ route('admin.whatsapp-numbers.index', ['assignment' => 'default']) }}" class="btn btn-{{ request('assignment') === 'default' ? 'info' : 'outline-info' }} btn-sm">{{ __('Default') }}</a>
                    <a href="{{ route('admin.whatsapp-numbers.index', ['assignment' => 'company']) }}" class="btn btn-{{ request('assignment') === 'company' ? 'secondary' : 'outline-secondary' }} btn-sm">{{ __('Company') }}</a>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.whatsapp-compose.create') }}" class="btn btn-outline-primary btn-sm">{{ __('Send WhatsApp') }}</a>
                    <a href="{{ route('admin.whatsapp-numbers.create') }}" class="btn btn-primary btn-sm">{{ __('Add Number') }}</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Label') }}</th>
                            <th>{{ __('Phone Number ID') }}</th>
                            <th>{{ __('Display') }}</th>
                            <th>{{ __('Assignment') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($numbers as $number)
                            <tr>
                                <td>{{ $number->label ?: '—' }}</td>
                                <td><code>{{ $number->phone_number_id }}</code></td>
                                <td>{{ $number->display_phone_number ?: '—' }}</td>
                                <td>
                                    @if($number->is_default)
                                        <span class="badge bg-info">{{ __('Default') }}</span>
                                    @elseif($number->company)
                                        <span class="badge bg-secondary">{{ $number->company->name }}</span>
                                    @else
                                        <span class="text-muted">{{ __('Unassigned') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($number->isActive())
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td>{{ $number->updated_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.whatsapp-numbers.edit', $number) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    <form action="{{ route('admin.whatsapp-numbers.destroy', $number) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm(@json(__('Delete this WhatsApp number?')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">{{ __('No WhatsApp numbers configured yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $numbers->links() }}
        </div>
    </div>
</div>
@endsection
