@extends('layouts.app')

@section('title','Inventory Items')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 210px);
    }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>

<div class="d-flex gap-2 m-3">
    <a href="{{ route('inventory.index') }}" 
       class="btn btn-pill {{ request()->routeIs('inventory.index') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Inventory Items
    </a>
    <a href="{{ route('inventory.indexBatch') }}" 
       class="btn btn-pill {{ request()->routeIs('inventory.indexBatch') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Purchase History
    </a>
</div>

<div class="content mt-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Search by item name..." value="{{ request('quick_search') }}">
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('items.table')
        </div>
    </div>
</div>
@endsection

@section('page-script')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
function confirmDelete(url) {
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
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'An error occurred while deleting.', 'error');
            });
        }
    });
}
$(document).ready(function () {
    $('#quickSearch').on('keyup', function (e) {
        if (e.keyCode === 13 || $(this).val().length === 0) {
            const searchValue = $(this).val();
            const url = new URL(window.location.href);

            if (searchValue) {
                url.searchParams.set('quick_search', searchValue);
            } else {
                url.searchParams.delete('quick_search');
            }
            url.searchParams.delete('page');

            window.location.href = url.toString();
        }
    });
});
</script>
@endsection
