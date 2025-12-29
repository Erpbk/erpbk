@extends('layouts.app')
@section('title', $config['name'] . ' - Recycle Bin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fa fa-trash-o text-danger"></i> {{ $config['name'] }} Recycle Bin
                    </h4>
                    <p class="text-muted mb-0">
                        <small>View and restore deleted {{ strtolower($config['name']) }} records</small>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route($config['index_route']) }}" class="btn btn-primary">
                        <i class="fa fa-arrow-left"></i> Back to {{ $config['name'] }}
                    </a>
                    <span class="badge bg-info fs-6 ms-2">
                        <i class="fa fa-database"></i> {{ $data->total() }} Deleted Items
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search in deleted records..." 
                           value="{{ $searchQuery }}">
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

    <!-- Deleted Records -->
    @if($data->count() > 0)
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="80">ID</th>
                            <th>Details</th>
                            <th width="180">Deleted</th>
                            <th width="200" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $item)
                        <tr>
                            <td><strong>#{{ $item->id }}</strong></td>
                            <td>
                                @foreach($config['display_columns'] as $column)
                                @if(!empty($item->$column))
                                <span class="me-3">
                                    <strong>{{ ucfirst(str_replace('_', ' ', $column)) }}:</strong>
                                    {{ $item->$column }}
                                </span>
                                @endif
                                @endforeach
                            </td>
                            <td>
                                <small class="text-muted">
                                    <i class="fa fa-clock-o"></i> {{ $item->deleted_at->format('Y-m-d H:i') }}
                                </small>
                                <br>
                                <small class="text-danger">
                                    <i class="fa fa-calendar"></i> {{ $item->deleted_at->diffForHumans() }}
                                </small>
                            </td>
                            <td class="text-center">
                                @if(auth()->user()->can('trash_restore'))
                                <form action="{{ route(str_replace('.trash', '.restore', Route::currentRouteName()), $item->id) }}" 
                                      method="POST" style="display: inline-block;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm"
                                        onclick="return confirm('Restore this {{ strtolower($config['name']) }}?')">
                                        <i class="fa fa-undo"></i> Restore
                                    </button>
                                </form>
                                @endif

                                @if(auth()->user()->can('trash_force_delete'))
                                <form action="{{ route(str_replace('.trash', '.force-destroy', Route::currentRouteName()), $item->id) }}" 
                                      method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('WARNING! This will PERMANENTLY delete this record. This action CANNOT be undone! Are you absolutely sure?')">
                                        <i class="fa fa-trash-o"></i> Delete Forever
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="card mt-3">
        <div class="card-body">
            {{ $data->links() }}
        </div>
    </div>

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
                @if($searchQuery)
                No Results Found
                @else
                No Deleted {{ $config['name'] }}
                @endif
            </h3>

            <p class="text-muted mb-4" style="font-size: 1.1rem; max-width: 500px; margin: 0 auto;">
                @if($searchQuery)
                No deleted records match your search query "<strong>{{ $searchQuery }}</strong>".
                @else
                Great! There are no deleted {{ strtolower($config['name']) }} records in the recycle bin.
                @endif
            </p>

            <div class="mt-4">
                <a href="{{ route($config['index_route']) }}" class="btn btn-primary btn-lg px-5 py-3" 
                   style="border-radius: 50px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                    <i class="fa fa-arrow-left me-2"></i> Back to {{ $config['name'] }}
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Info Box -->
    <div class="alert alert-info mt-3">
        <i class="fa fa-info-circle"></i>
        <strong>How it works:</strong>
        <ul class="mb-0 mt-2">
            <li>Deleted records are kept in the recycle bin for 90 days</li>
            <li>You can restore any record with the <span class="badge bg-success">Restore</span> button</li>
            <li>Permanently deleted records cannot be recovered</li>
            <li>Only users with proper permissions can manage deleted records</li>
        </ul>
    </div>
</div>
@endsection

