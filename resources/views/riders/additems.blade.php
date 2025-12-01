{!! Form::open(['url' => route('riders.storeitems', $rider->id), 'method' => 'POST', 'id'=>'formajax']) !!}
@csrf
<div class="card-body">
    <div class="row">
        @include('riders.itemsfields')
    </div>
</div>
<div class="card-footer bg-light border-top">
    <div class="d-flex justify-content-end gap-3">
        <button type="submit" class="btn btn-primary px-4">Save Items</button>
    </div>
</div>
{!! Form::close() !!}

<script>
    $(document).ready(function() {
        $('#formajax').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');

            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        // Refresh the items list or close modal
                        if (typeof refreshItemsList === 'function') {
                            refreshItemsList();
                        }
                        $('.modal').modal('hide');
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('An error occurred while saving items');
                    }
                }
            });
        });
    });