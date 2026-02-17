@extends('banks.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>
        @can('cheques_view')
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                @can('cheques_create')
                    <button class="btn btn-primary btn-sm show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Cheque" data-action="{{ route('cheques.create') }}?id={{ request()->segment(3) }}">Add New</button>
                @endcan
            </div>
            <div class="card-body table-responsive py-0" id="table-data">
                @include('cheques.table')
            </div>
        </div>
        @endcan
        @cannot('cheques_view')
            <div class="text-center mt-5">
                <h3>You do not have permission to view Cheques.</h3> 
            </div>
        @endcannot
    </div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        $(document).on('click', '.delete-cheque', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Cheque has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Failed to delete Cheque. ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'),
                                'error'
                            );
                        }
                    });
                }
            });
        });
    })
</script>

@endsection