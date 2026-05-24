@php
$companySlug = request()->route('company_slug');
$isEmployeeEditPage = request()->routeIs('employees.edit');
@endphp
@if(!$isEmployeeEditPage && isset($result))
<div class="card-footer border-top fixed-footer mt-3" style="padding-top: 25px;">
    <div class="d-flex justify-content-start gap-2 flex-wrap">
        @can('employees_edit')
        <a href="{{ route('employees.edit', ['company_slug' => $companySlug, 'employee' => $result['id']]) }}" class="btn btn-outline-primary btn-sm waves-effect waves-light">
            <i class="fa fa-edit"></i>&nbsp;Edit
        </a>
        @endcan
        @can('email_create')
        <a href="javascript:void(0);" data-action="{{ route('employee.sendemail', ['company_slug' => $companySlug, 'id' => $result['id']]) }}" data-size="md"
            data-title="{{ ($result['name'] ?? 'Employee') . ' (' . ($result['employee_id'] ?? '') . ')' }}"
            class="btn btn-outline-warning btn-sm show-modal text-nowrap">
            <i class="fas fa-envelope"></i>&nbsp;Send Email
        </a>
        @endcan
    </div>
</div>
@endif
