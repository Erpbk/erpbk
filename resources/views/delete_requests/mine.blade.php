@extends($layout ?? 'layouts.app')
@section('title', 'My Delete Requests')
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">My Delete Requests</h4>
                    @can('settings_delete_requests_view')
                    <a href="{{ route('settings-panel.delete-requests.index') }}" class="btn btn-sm btn-outline-primary">Admin Queue</a>
                    @endcan
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All statuses</option>
                                @foreach(['pending','approved','rejected'] as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit">Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Module</th>
                                    <th>Record</th>
                                    <th>Requested At</th>
                                    <th>Status</th>
                                    <th>Reviewed At</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deleteRequests as $req)
                                    <tr>
                                        <td>#{{ $req->id }}</td>
                                        <td>{{ $req->module_name }}</td>
                                        <td>{{ $req->record_label ?: ('#' . $req->deletable_id) }}</td>
                                        <td>{{ $req->created_at?->format('d-m-Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-label-{{ $statusColors[$req->status] ?? 'secondary' }}">
                                                {{ ucfirst($req->status) }}
                                            </span>
                                            @if($req->bin_outcome === 'in_recycle_bin')
                                                <div class="small text-warning">In Recycle Bin</div>
                                            @elseif($req->bin_outcome === 'restored')
                                                <div class="small text-primary">Restored</div>
                                            @elseif($req->bin_outcome === 'permanently_deleted')
                                                <div class="small text-danger">Permanently deleted</div>
                                            @endif
                                        </td>
                                        <td>{{ $req->reviewed_at?->format('d-m-Y H:i') ?? '—' }}</td>
                                        <td>
                                            <a href="{{ route('settings-panel.delete-requests.show', $req) }}" class="btn btn-sm btn-outline-primary">Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">You have not submitted any delete requests.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $deleteRequests->withQueryString()->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
