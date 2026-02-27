@extends($layout ?? 'layouts.app')
@section('title', 'Activity Log Details')

@section('page-style')
<link rel="stylesheet" href="{{ asset('css/activity-logs.css') }}">
@endsection

@section('content')
@php $activityLogsRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.activity-logs' : 'activity-logs'; @endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Activity Log Details</h4>
                    <a href="{{ route($activityLogsRoute . '.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left"></i> Back to Logs
                    </a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>ID:</strong></td>
                                            <td>{{ $activityLog->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Action:</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $activityLog->action == 'created' ? 'success' : ($activityLog->action == 'updated' ? 'warning' : ($activityLog->action == 'deleted' ? 'danger' : 'info')) }}">
                                                    {{ ucfirst(str_replace('_', ' ', $activityLog->action)) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Module:</strong></td>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst($activityLog->module_name) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Model:</strong></td>
                                            <td>
                                                @if($activityLog->model_type && $activityLog->model_id)
                                                <span class="text-muted">{{ $activityLog->model_type }} #{{ $activityLog->model_id }}</span>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>IP Address:</strong></td>
                                            <td>{{ $activityLog->ip_address ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Timestamp:</strong></td>
                                            <td>{{ $activityLog->created_at->format('M d, Y H:i:s') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">User Information</h6>
                                </div>
                                <div class="card-body">
                                    @if($activityLog->user)
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar avatar-lg me-3">
                                            <span class="avatar-initial rounded-circle bg-primary fs-4">{{ substr($activityLog->user->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $activityLog->user->name }}</h6>
                                            <small class="text-muted">{{ $activityLog->user->email }}</small>
                                        </div>
                                    </div>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>User ID:</strong></td>
                                            <td>{{ $activityLog->user->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $activityLog->user->email }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>{{ $activityLog->user->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    </table>
                                    @else
                                    <div class="text-center text-muted">
                                        <i class="ti ti-user-off ti-lg mb-2"></i>
                                        <p>System Action</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($activityLog->changes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Changes Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @if(isset($activityLog->changes['old']) && isset($activityLog->changes['new']))
                                        <!-- Updated record with highlighted changes -->
                                        @if(isset($activityLog->changed_fields) && count($activityLog->changed_fields) > 0)
                                        <div class="col-12">
                                            <h6 class="text-primary mb-3">Changed Fields Only</h6>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Field</th>
                                                            <th>Previous Value</th>
                                                            <th>New Value</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($activityLog->changed_fields as $field => $change)
                                                        <tr class="bg-light-warning">
                                                            <td><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong></td>
                                                            <td class="text-danger">
                                                                @if(is_array($change['old']))
                                                                <pre class="mb-0">{{ json_encode($change['old'], JSON_PRETTY_PRINT) }}</pre>
                                                                @else
                                                                {{ $change['old'] ?: '-' }}
                                                                @endif
                                                            </td>
                                                            <td class="text-success">
                                                                @if(is_array($change['new']))
                                                                <pre class="mb-0">{{ json_encode($change['new'], JSON_PRETTY_PRINT) }}</pre>
                                                                @else
                                                                {{ $change['new'] ?: '-' }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="mt-3">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="showAllFields">
                                                    Show All Fields
                                                </button>
                                            </div>

                                            <div id="allFieldsContainer" class="mt-3" style="display: none;">
                                                <h6 class="text-secondary mb-3">All Fields</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Field</th>
                                                                <th>Previous Value</th>
                                                                <th>New Value</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($activityLog->highlighted_changes as $field => $change)
                                                            <tr class="{{ $change['changed'] ? 'bg-light-warning' : '' }}">
                                                                <td><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong></td>
                                                                <td class="{{ $change['changed'] ? 'text-danger' : 'text-muted' }}">
                                                                    @if(is_array($change['old']))
                                                                    <pre class="mb-0">{{ json_encode($change['old'], JSON_PRETTY_PRINT) }}</pre>
                                                                    @else
                                                                    {{ $change['old'] ?: '-' }}
                                                                    @endif
                                                                </td>
                                                                <td class="{{ $change['changed'] ? 'text-success' : 'text-muted' }}">
                                                                    @if(is_array($change['new']))
                                                                    <pre class="mb-0">{{ json_encode($change['new'], JSON_PRETTY_PRINT) }}</pre>
                                                                    @else
                                                                    {{ $change['new'] ?: '-' }}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        @elseif(isset($activityLog->highlighted_changes))
                                        <div class="col-12">
                                            <h6 class="text-primary mb-3">No Changes Detected</h6>
                                            <div class="alert alert-info">
                                                No changes were detected between the old and new values.
                                            </div>

                                            <div class="mt-3">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="showAllFields">
                                                    Show All Fields
                                                </button>
                                            </div>

                                            <div id="allFieldsContainer" class="mt-3" style="display: none;">
                                                <h6 class="text-secondary mb-3">All Fields</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Field</th>
                                                                <th>Previous Value</th>
                                                                <th>New Value</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($activityLog->highlighted_changes as $field => $change)
                                                            <tr>
                                                                <td><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong></td>
                                                                <td class="text-muted">
                                                                    @if(is_array($change['old']))
                                                                    <pre class="mb-0">{{ json_encode($change['old'], JSON_PRETTY_PRINT) }}</pre>
                                                                    @else
                                                                    {{ $change['old'] ?: '-' }}
                                                                    @endif
                                                                </td>
                                                                <td class="text-muted">
                                                                    @if(is_array($change['new']))
                                                                    <pre class="mb-0">{{ json_encode($change['new'], JSON_PRETTY_PRINT) }}</pre>
                                                                    @else
                                                                    {{ $change['new'] ?: '-' }}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        @else
                                        <!-- Fallback to original display if highlighting is not available -->
                                        <div class="col-md-6">
                                            <h6 class="text-danger mb-3">Previous Values</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless">
                                                    @foreach($activityLog->changes['old'] as $key => $value)
                                                    <tr>
                                                        <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong></td>
                                                        <td class="text-muted">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                    </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-success mb-3">New Values</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless">
                                                    @foreach($activityLog->changes['new'] as $key => $value)
                                                    <tr>
                                                        <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong></td>
                                                        <td class="text-muted">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                    </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </div>
                                        @endif
                                        @elseif(isset($activityLog->changes['new']))
                                        <!-- Created record -->
                                        <div class="col-12">
                                            <h6 class="text-success mb-3">New Record Data</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless">
                                                    @foreach($activityLog->changes['new'] as $key => $value)
                                                    <tr>
                                                        <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong></td>
                                                        <td class="text-muted">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                    </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </div>
                                        @elseif(isset($activityLog->changes['old']))
                                        <!-- Deleted record -->
                                        <div class="col-12">
                                            <h6 class="text-danger mb-3">Deleted Record Data</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless">
                                                    @foreach($activityLog->changes['old'] as $key => $value)
                                                    <tr>
                                                        <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong></td>
                                                        <td class="text-muted">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                    </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </div>
                                        @else
                                        <!-- Custom changes -->
                                        <div class="col-12">
                                            <h6 class="text-info mb-3">Custom Data</h6>
                                            <pre class="bg-light p-3 rounded"><code>{{ json_encode($activityLog->changes, JSON_PRETTY_PRINT) }}</code></pre>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($activityLog->model_type && $activityLog->model_id)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Related Model</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <p class="mb-0">
                                            <strong>Model:</strong> {{ $activityLog->model_type }}<br>
                                            <strong>ID:</strong> {{ $activityLog->model_id }}
                                        </p>
                                        <p class="mb-0 mt-2">
                                            <em>This action was performed on the above model record.</em>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const showAllFieldsBtn = document.getElementById('showAllFields');
        const allFieldsContainer = document.getElementById('allFieldsContainer');

        if (showAllFieldsBtn && allFieldsContainer) {
            showAllFieldsBtn.addEventListener('click', function() {
                if (allFieldsContainer.style.display === 'none') {
                    allFieldsContainer.style.display = 'block';
                    showAllFieldsBtn.textContent = 'Hide All Fields';
                } else {
                    allFieldsContainer.style.display = 'none';
                    showAllFieldsBtn.textContent = 'Show All Fields';
                }
            });
        }
    });
</script>
@endsection