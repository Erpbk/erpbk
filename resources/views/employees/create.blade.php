@extends('employees.view')

@section('page_content')
{!! Form::open(['route' => 'employees.store', 'id' => 'formajax', 'class' => 'form-with-fixed-footer']) !!}
<input type="hidden" id="redirect_url" value="{{ route('employees.index') }}" />
<input type="hidden" name="account" value="new" />
<div class="card-body card-body-with-footer">
    @include('employees.fields')
</div>
<div class="card-footer bg-light border-top fixed-footer">
    <div class="d-flex justify-content-end gap-3">
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Information</button>
    </div>
</div>
{!! Form::close() !!}
@endsection

@push('page-scripts')
<script>
    $(document).ready(function() {
        $('#formajax').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const originalText = submitButton.html();
            submitButton.html('<i class="fa fa-spinner fa-spin me-2"></i>Creating...').prop('disabled', true);

            const formData = new FormData(this);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'Employee created successfully');
                        setTimeout(function() {
                            window.location.href = $('#redirect_url').val();
                        }, 800);
                        return;
                    }
                    submitButton.html(originalText).prop('disabled', false);
                    toastr.error(response.message || 'Failed to create employee');
                },
                error: function(xhr) {
                    submitButton.html(originalText).prop('disabled', false);
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const messages = [];
                        Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                            messages.push(xhr.responseJSON.errors[key][0]);
                        });
                        toastr.error(messages.join('\n'));
                        return;
                    }
                    toastr.error('Failed to create employee');
                }
            });
        });
    });
</script>
@endpush