@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Assign Riders to Recruiter: {{ $recruiter->name }}</h3>
        </div>
        <div class="card-body">
            <form id="assignRidersForm">
                @csrf
                <input type="hidden" name="recruiter_id" value="{{ $recruiter->id }}">

                <div class="form-group">
                    <label for="unassignedRiders">Select Riders to Assign (Click to assign immediately)</label>
                    <select multiple class="form-control from-select select2" id="unassignedRiders" name="rider_ids[]" style="width: 100%;">
                        @foreach (DB::table('riders')->where('recuriter' , null)->get() as $r)
                        <option value="{{ $r->id }}">{{ $r->rider_id }}-{{ $r->name }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Select one or more riders. You can also click on a single rider to assign them immediately.</small>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Assign Selected Riders</button>
                <a href="{{ route('recruiters.show', $recruiter->id) }}" class="btn btn-secondary mt-3">Back to Recruiter</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        const assignRoute = "{{ route('recruiters.assign-riders', $recruiter->id) }}";
        let allRiders = [];
        let isProcessing = false; // Prevent double-click issues

        // Initialize select2 first
        const $select = $('#unassignedRiders');
        $select.select2({
            placeholder: 'Select riders to assign',
            allowClear: true,
            width: '100%'
        });



        // Function to assign riders
        function assignRiders(riderIds, shouldRedirect = true) {
            $.ajax({
                url: assignRoute,
                method: 'POST',
                data: {
                    _token: $('input[name="_token"]').val(),
                    rider_ids: riderIds
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Riders assigned successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    if (shouldRedirect) {
                        setTimeout(() => {
                            window.location.href = "{{ route('recruiters.show', $recruiter->id) }}";
                        }, 2000);
                    } else {
                        // Just reload the dropdown
                        setTimeout(() => {}, 500);
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to assign riders'
                    });
                }
            });
        }

        // Handle form submission for multiple riders
        $('#assignRidersForm').on('submit', function(e) {
            e.preventDefault();

            const riderIds = $select.val();

            if (!riderIds || riderIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one rider to assign'
                });
                return;
            }

            assignRiders(riderIds, true);
        });
    });
</script>
@endsection