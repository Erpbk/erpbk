@extends('layouts.app')
@section('title', 'Recycle Bin')

@push('styles')
<style>
    .empty-state-icon .icon-wrapper {
        animation: pulse-scale 2s ease-in-out infinite;
    }

    @keyframes pulse-scale {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    .empty-state-icon .icon-wrapper:hover {
        transform: scale(1.1) !important;
        transition: transform 0.3s ease;
    }

    .btn-lg {
        transition: all 0.3s ease;
    }

    .btn-lg:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6) !important;
    }

    .cascade-arrow {
        transition: transform 0.3s ease;
    }

    tr:hover .cascade-arrow {
        transform: translateX(5px);
        color: #667eea !important;
    }

    /* Riders-style table improvements */
    .table-striped tbody tr {
        transition: all 0.2s ease;
    }

    .table-striped tbody tr:hover {
        background-color: #f8f9fa !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .dataTable thead th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 8px;
    }

    .dataTable tbody td {
        vertical-align: middle;
        padding: 12px 8px;
    }

    .avatar-wrapper {
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .dropdown-menu {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e0e0e0;
    }

    .dropdown-item:hover {
        background-color: #f5f5f5;
    }

    .badge {
        padding: 6px 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fa fa-trash-o text-danger"></i> Recycle Bin
                    </h4>
                    <p class="text-muted mb-0">
                        <small>View and restore deleted records from all modules</small>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-info fs-6">
                        <i class="fa fa-database"></i> {{ $totalCount }} Total Items
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('trash.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Module</label>
                    <select name="module" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ $currentModule == 'all' ? 'selected' : '' }}>All Modules</option>
                        @foreach($modules as $key => $config)
                        <option value="{{ $key }}" {{ $currentModule == $key ? 'selected' : '' }}>
                            {{ $config['name'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search in deleted records..." value="{{ $searchQuery }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('flash::message')

    {{-- DEBUG: Temporary - Remove after testing --}}
    @if(config('app.debug'))
    <!-- <div class="alert alert-info">
        <strong>Debug Info (only visible in debug mode):</strong>
        @foreach($trashedRecords as $debugItem)
        <div class="mt-2">
            <strong>{{ $debugItem['module_name'] }} #{{ $debugItem['id'] }}</strong><br>
            - caused_by: {{ $debugItem['caused_by'] ? 'YES (' . class_basename($debugItem['caused_by']->primary_model) . ' #' . $debugItem['caused_by']->primary_id . ')' : 'NO' }}<br>
            - cascaded_to: {{ $debugItem['cascaded_to'] ? count($debugItem['cascaded_to']) . ' records' : 'NONE' }}
            @if($debugItem['cascaded_to'] && count($debugItem['cascaded_to']) > 0)
            <br>&nbsp;&nbsp;└─
            @foreach($debugItem['cascaded_to'] as $c)
            {{ class_basename($c->related_model) }} #{{ $c->related_id }}{{ !$loop->last ? ', ' : '' }}
            @endforeach
            @endif
        </div>
        @endforeach
    </div> -->
    @endif

    <!-- Deleted Records -->
    @if(count($trashedRecords) > 0)
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped dataTable no-footer mb-0">
                    <thead class="text-center">
                        <tr role="row">
                            <th class="sorting" tabindex="0" rowspan="1" colspan="1">ID</th>
                            <th class="sorting" tabindex="0" rowspan="1" colspan="1">Module</th>
                            <th class="sorting" tabindex="0" rowspan="1" colspan="1">Record Details</th>
                            <th class="sorting" tabindex="0" rowspan="1" colspan="1">Deleted By</th>
                            <th class="sorting" tabindex="0" rowspan="1" colspan="1">Deleted At</th>
                            <th class="sorting" tabindex="0" rowspan="1" colspan="1">Cascade Info</th>
                            <th class="text-center" tabindex="0" rowspan="1" colspan="1">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @foreach($trashedRecords as $item)
                        <tr class="text-center">
                            <!-- ID -->
                            <td>
                                <strong class="text-primary">#{{ $item['id'] }}</strong>
                            </td>

                            <!-- Module Badge -->
                            <td>
                                <span class="badge bg-secondary">
                                    <i class="fa {{ $item['icon'] }}"></i>
                                    {{ $item['module_name'] }}
                                </span>
                            </td>

                            <!-- Record Details -->
                            <td>
                                <div class="small">
                                    @foreach($item['display_columns'] as $column)
                                    @if(!empty($item['record']->$column))
                                    <span class="d-block mb-1">
                                        <strong class="text-dark">{{ ucfirst(str_replace('_', ' ', $column)) }}:</strong>
                                        <span class="text-muted">{{ $item['record']->$column }}</span>
                                    </span>
                                    @endif
                                    @endforeach
                                </div>
                            </td>

                            <!-- Deleted By -->
                            <td>
                                @if($item['deleted_by_user'])
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="avatar-wrapper me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa fa-user text-white"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block text-dark">{{ $item['deleted_by_user']->name }}</strong>
                                        <small class="text-muted">ID: {{ $item['deleted_by_user']->id }}</small>
                                    </div>
                                </div>
                                @else
                                <span class="badge bg-label-secondary">
                                    <i class="fa fa-cog"></i> System
                                </span>
                                @endif
                            </td>

                            <!-- Deletion Time -->
                            <td>
                                <div>
                                    <div class="mb-1">
                                        <i class="fa fa-clock-o text-primary"></i>
                                        <strong class="text-dark">{{ $item['deleted_at']->format('M d, Y') }}</strong>
                                    </div>
                                    <small class="text-muted d-block">
                                        {{ $item['deleted_at']->format('h:i A') }}
                                    </small>
                                    <small class="text-danger d-block">
                                        <i class="fa fa-calendar"></i> {{ $item['deleted_at']->diffForHumans() }}
                                    </small>
                                </div>
                            </td>

                            <!-- Cascade Information -->
                            <td>
                                {{-- Show if this was deleted by cascade --}}
                                @if(isset($item['caused_by']) && $item['caused_by'])
                                <div class="mb-2">
                                    <span class="badge bg-warning text-dark d-block mb-1">
                                        <i class="fa fa-level-up"></i> Caused By
                                    </span>
                                    <small class="d-block">
                                        <strong>{{ class_basename($item['caused_by']->primary_model) }}</strong>
                                    </small>
                                    <small class="d-block text-muted">
                                        {{ $item['caused_by']->primary_name }}
                                    </small>
                                    <small class="d-block text-muted">
                                        (ID: {{ $item['caused_by']->primary_id }})
                                    </small>
                                </div>
                                @endif

                                {{-- Show if this deletion caused other deletions --}}
                                @if(isset($item['cascaded_to']) && $item['cascaded_to'] && count($item['cascaded_to']) > 0)
                                <div>
                                    <span class="badge bg-info d-block mb-1">
                                        <i class="fa fa-level-down"></i> Cascaded To ({{ count($item['cascaded_to']) }})
                                    </span>
                                    @foreach($item['cascaded_to'] as $cascade)
                                    <small class="d-block mb-1">
                                        <i class="fa fa-arrow-right text-muted"></i>
                                        <strong>{{ class_basename($cascade->related_model) }}</strong>:
                                        {{ $cascade->related_name }}
                                        <span class="text-muted">(#{{ $cascade->related_id }})</span>
                                    </small>
                                    @endforeach
                                </div>
                                @endif

                                {{-- If no cascade info --}}
                                @if((!isset($item['caused_by']) || !$item['caused_by']) && (!isset($item['cascaded_to']) || !$item['cascaded_to'] || count($item['cascaded_to']) == 0))
                                <small class="text-muted">
                                    <i class="fa fa-minus-circle"></i> None
                                </small>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $item['id'] }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $item['id'] }}">
                                        @if($item['can_restore'])
                                        <a href="#" class="dropdown-item waves-effect restore-item" data-form-id="restore-form-{{ $item['id'] }}">
                                            <i class="fa fa-undo text-success my-1"></i> Restore
                                        </a>
                                        <form id="restore-form-{{ $item['id'] }}" action="{{ route('trash.restore', [$item['module'], $item['id']]) }}" method="POST" style="display: none;">
                                            @csrf
                                        </form>
                                        @endif

                                        @if($item['can_force_delete'])
                                        <a href="#" class="dropdown-item waves-effect delete-item" data-form-id="delete-form-{{ $item['id'] }}">
                                            <i class="fa fa-trash-o text-danger my-1"></i> Delete Forever
                                        </a>
                                        <form id="delete-form-{{ $item['id'] }}" action="{{ route('trash.force-destroy', [$item['module'], $item['id']]) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if($totalPages > 1)
    <div class="card mt-3">
        <div class="card-body">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $currentPage - 1])) }}">
                            Previous
                        </a>
                    </li>

                    @for($i = 1; $i <= $totalPages; $i++)
                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                        <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $i])) }}">
                            {{ $i }}
                        </a>
                        </li>
                        @endfor

                        <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ route('trash.index', array_merge(request()->all(), ['page' => $currentPage + 1])) }}">
                                Next
                            </a>
                        </li>
                </ul>
            </nav>
            <p class="text-center text-muted mt-2 mb-0">
                Showing {{ count($trashedRecords) }} of {{ $totalCount }} deleted records
            </p>
        </div>
    </div>
    @endif

    @else
    <!-- Empty State -->
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="empty-state-icon mb-4">
                <div class="icon-wrapper mx-auto" style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);">
                    <i class="fa fa-check-circle text-white" style="font-size: 4rem;"></i>
                </div>
            </div>

            <h3 class="mt-4 mb-3" style="color: #2d3748; font-weight: 600;">
                @if($currentModule != 'all')
                No Deleted {{ ucfirst($currentModule) }} Found
                @elseif($searchQuery)
                No Results Found
                @else
                Recycle Bin is Empty
                @endif
            </h3>

            <p class="text-muted mb-4" style="font-size: 1.1rem; max-width: 500px; margin: 0 auto;">
                @if($currentModule != 'all')
                There are no deleted {{ $currentModule }} in the recycle bin. All {{ $currentModule }} records are active.
                @elseif($searchQuery)
                No deleted records match your search query "<strong>{{ $searchQuery }}</strong>". Try different keywords or clear the search.
                @else
                Great! Your recycle bin is completely empty. All records are active and no deletions have been made recently.
                @endif
            </p>

            @if($currentModule != 'all' || $searchQuery)
            <div class="mt-4">
                <a href="{{ route('trash.index') }}" class="btn btn-primary btn-lg px-5 py-3" style="border-radius: 50px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                    <i class="fa fa-refresh me-2"></i> View All Deleted Records
                </a>
            </div>
            @else
            <div class="mt-4 p-4 bg-light rounded" style="max-width: 600px; margin: 0 auto;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-info-circle text-primary me-2 mt-1"></i>
                            <div>
                                <strong>Soft Delete Protection</strong>
                                <p class="text-muted small mb-0">Deleted records are stored here for 90 days before permanent removal.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-undo text-success me-2 mt-1"></i>
                            <div>
                                <strong>Easy Recovery</strong>
                                <p class="text-muted small mb-0">Restore deleted records with one click anytime.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-shield text-warning me-2 mt-1"></i>
                            <div>
                                <strong>Data Safety</strong>
                                <p class="text-muted small mb-0">Protected from accidental permanent deletion.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-history text-info me-2 mt-1"></i>
                            <div>
                                <strong>Full Audit Trail</strong>
                                <p class="text-muted small mb-0">Track who deleted what and when.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Info Box -->
    <div class="alert alert-info mt-3">
        <i class="fa fa-info-circle"></i>
        <strong>How it works:</strong>
        <ul class="mb-0 mt-2">
            <li>Deleted records are kept in the recycle bin for 90 days (configurable)</li>
            <li>You can restore any record with the <span class="badge bg-success">Restore</span> button</li>
            <li>Permanently deleted records cannot be recovered</li>
            <li>Only users with proper permissions can see and manage deleted records</li>
            <li><strong>Cascade Info Column:</strong> Shows which deletion caused this record to be deleted, or what other records were deleted because of this one</li>
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Auto-submit form when module filter changes
        $('select[name="module"]').on('change', function() {
            $(this).closest('form').submit();
        });

        // Handle restore action
        $('.restore-item').on('click', function(e) {
            e.preventDefault();
            var formId = $(this).data('form-id');
            if (confirm('Are you sure you want to restore this record?')) {
                $('#' + formId).submit();
            }
        });

        // Handle delete forever action
        $('.delete-item').on('click', function(e) {
            e.preventDefault();
            var formId = $(this).data('form-id');
            if (confirm('WARNING! This will PERMANENTLY delete this record. This action CANNOT be undone! Are you absolutely sure?')) {
                $('#' + formId).submit();
            }
        });
    });
</script>
@endpush