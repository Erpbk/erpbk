@extends('employees.view')

@section('page_content')
{!! Form::open(['route' => 'employees.store', 'id' => 'employee-store-form', 'class' => 'form-with-fixed-footer employee-ajax-form', 'data-reload-table' => '0']) !!}
<input type="hidden" id="redirect_url" value="{{ route('employees.index') }}" />
<input type="hidden" name="account" value="new" />
@php
$hasEmployeeIdField = false;
foreach ($fieldsByCategory ?? [] as $group) {
    foreach ($group->fields as $item) {
        if (($item->kind ?? '') === 'fixed' && ($item->field_key ?? '') === 'employee_id') {
            $hasEmployeeIdField = true;
            break 2;
        }
    }
}
@endphp
@if(!$hasEmployeeIdField)
<input type="hidden" name="employee_id" value="{{ $empId }}">
@endif
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
        const $form = $('#employee-store-form');
        if (!$form.length) {
            return;
        }

        $form.off('submit.employeeStore').on('submit.employeeStore', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if ($form.data('submitting')) {
                return false;
            }
            $form.data('submitting', true);

            const submitButton = $form.find('button[type="submit"]');
            const originalText = submitButton.html();
            submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>Creating...');

            const formData = new FormData(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                success: function(response) {
                    if (response && response.success) {
                        toastr.success(response.message || 'Employee created successfully');
                        const redirect = response.redirect || $('#redirect_url').val();
                        if (redirect) {
                            setTimeout(function() {
                                window.location.href = redirect;
                            }, 800);
                        }
                        return;
                    }
                    $form.data('submitting', false);
                    submitButton.prop('disabled', false).html(originalText);
                    toastr.error((response && response.message) || 'Failed to create employee');
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
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to create employee');
                }
            });

            return false;
        });
    });
</script>
@endpush
