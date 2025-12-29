@extends('banks.viewindex')
@section('page_content')

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fa fa-trash"></i> Deleted Banks</h5>
    </div>
    
    <div class="card-body">
        @include('flash::message')
        
        @if($data->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Account No</th>
                            <th>IBAN</th>
                            <th>Branch</th>
                            <th>Account Type</th>
                            <th>Status</th>
                            <th>Deleted At</th>
                            <th width="150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $bank)
                            <tr>
                                <td>{{ $bank->id }}</td>
                                <td>{{ $bank->name }}</td>
                                <td>{{ $bank->title }}</td>
                                <td>{{ $bank->account_no }}</td>
                                <td>{{ $bank->iban }}</td>
                                <td>{{ $bank->branch }}</td>
                                <td>{{ $bank->account_type }}</td>
                                <td>
                                    @if($bank->status == 1)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $bank->deleted_at->format('Y-m-d H:i') }}</small><br>
                                    <small class="text-muted">{{ $bank->deleted_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @can('bank_restore')
                                        <form action="{{ route('banks.restore', $bank->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to restore this bank?')">
                                                <i class="fa fa-undo"></i> Restore
                                            </button>
                                        </form>
                                    @endcan
                                    
                                    @can('bank_force_delete')
                                        <form action="{{ route('banks.force-destroy', $bank->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('WARNING: This will PERMANENTLY delete this bank.\n\nThis action cannot be undone!\n\nAre you absolutely sure?')">
                                                <i class="fa fa-trash-o"></i> Permanent Delete
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted">Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} deleted banks</p>
                </div>
                <div>
                    {{ $data->links() }}
                </div>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> No deleted banks found.
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add confirmation dialog styling
    $('button[onclick*="confirm"]').on('click', function(e) {
        if (!$(this).hasClass('btn-danger')) {
            return true;
        }
        // Additional styling can be added here
    });
});
</script>
@endpush

