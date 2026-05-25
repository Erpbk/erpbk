@extends('employees.view')

@section('page_content')
{!! Form::model($employee, ['route' => ['employees.update', $employee->id], 'method' => 'patch', 'id' => 'employee-edit-form', 'class' => 'form-with-fixed-footer employee-ajax-form', 'data-reload-table' => '0']) !!}
<input type="hidden" id="redirect_url" value="{{ route('employees.show', $employee->id) }}" />
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
    $(function() {
        const $form = $('#employee-edit-form');
        if (!$form.length) {
            return;
        }

        $form.off('submit.employeeEdit').on('submit.employeeEdit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if ($form.data('submitting')) {
                return false;
            }
            $form.data('submitting', true);

            const submitButton = $form.find('button[type="submit"]');
            const originalText = submitButton.html();
            submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>Saving...');

            const formData = new FormData(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                success: function(response) {
                    if (response && response.success === true) {
                        toastr.success(response.message || 'Employee updated successfully');
                        if (response.employee && typeof window.refreshEmployeeSidebar === 'function') {
                            window.refreshEmployeeSidebar(response.employee);
                        }
                        const redirect = response.redirect || $('#redirect_url').val();
                        if (redirect) {
                            setTimeout(function() {
                                window.location.href = redirect;
                            }, 400);
                        }
                        return;
                    }
                    $form.data('submitting', false);
                    submitButton.prop('disabled', false).html(originalText);
                    toastr.error((response && response.message) || 'Failed to update employee');
                },
                error: function(xhr) {
                    $form.data('submitting', false);
                    submitButton.prop('disabled', false).html(originalText);
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const messages = [];
                        Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                            messages.push(xhr.responseJSON.errors[key][0]);
                        });
                        toastr.error(messages.join('\n'));
                        return;
                    }
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to update employee');
                }
            });

            return false;
        });
    });
</script>
@endpush