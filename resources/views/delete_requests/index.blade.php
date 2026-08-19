@extends($layout ?? 'layouts.app')
@section('title', 'Delete Requests')
@section('content')
@php
    $statusColors = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'secondary',
    ];
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h4 class="card-title mb-0">Delete Requests</h4>
                        <small class="text-muted">{{ $pendingCount }} pending approval</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings-panel.delete-requests.mine') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-user"></i> My Requests
                        </a>
                        <a href="{{ route('settings-panel.delete-requests.notifications') }}" class="btn btn-outline-info btn-sm">
                            <i class="ti ti-bell"></i> Notifications
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('settings-panel.delete-requests.index') }}" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Search label, module, reason, ID" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All statuses</option>
                                @foreach(['pending','approved','rejected'] as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="module_key" class="form-select">
                                <option value="">All modules</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module->module_key }}" @selected(request('module_key') === $module->module_key)>
                                        {{ $module->module_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('settings-panel.delete-requests.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Module</th>
                                    <th>Record</th>
                                    <th>Requested By</th>
                                    <th>Requested At</th>
                                    <th>Status</th>
                                    <th>Recycle Bin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deleteRequests as $req)
                                    <tr>
                                        <td>#{{ $req->id }}</td>
                                        <td>{{ $req->module_name }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $req->record_label ?: ('#' . $req->deletable_id) }}</div>
                                            <small class="text-muted">ID: {{ $req->deletable_id }}</small>
                                        </td>
                                        <td>{{ $req->requester->name ?? '—' }}</td>
                                        <td>{{ $req->created_at?->format('d-m-Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-label-{{ $statusColors[$req->status] ?? 'secondary' }}">
                                                {{ ucfirst($req->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($req->bin_outcome === 'in_recycle_bin')
                                                <span class="badge bg-label-warning">In Recycle Bin</span>
                                                <div class="small text-muted">{{ $req->moved_to_bin_at?->format('d-m-Y H:i') }}</div>
                                            @elseif($req->bin_outcome === 'payment_reversed')
                                                <span class="badge bg-label-success">Payment reversed</span>
                                            @elseif($req->bin_outcome === 'restored')
                                                <span class="badge bg-label-primary">Restored</span>
                                            @elseif($req->bin_outcome === 'permanently_deleted')
                                                <span class="badge bg-label-danger">Purged</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('settings-panel.delete-requests.show', $req) }}" class="btn btn-sm btn-outline-primary">
                                                {{ $req->isPending() ? 'View Record' : 'Details' }}
                                            </a>
                                            @if($req->isPending())
                                                <form action="{{ route('settings-panel.delete-requests.approve', $req) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success"
                                                        onclick="return confirm('Approve and move this record to the Recycle Bin? It will NOT be permanently deleted.')">
                                                        Approve → Bin
                                                    </button>
                                                </form>
                                                <form action="{{ route('settings-panel.delete-requests.reject', $req) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this delete request?')">Reject</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No delete requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $deleteRequests->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
