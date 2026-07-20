<!-- Action Buttons Component -->
@php
$companySlug = request()->route('company_slug');
$isRiderEditPage = request()->routeIs('riders.edit');
@endphp
@if(!$isRiderEditPage && isset($result))
<div class="card-footer border-top fixed-footer mt-3" style="padding-top: 25px;">
    <div class="d-flex justify-content-start gap-2 flex-wrap">
        @can('riders_rider_edit')
        <a href="{{ route('riders.edit', ['company_slug' => $companySlug, 'rider' => $result['id']]) }}" class="btn btn-outline-primary btn-sm waves-effect waves-light">
            <i class="fa fa-edit"></i>&nbsp;Edit
        </a>
        @endcan
        @can('email_create')
        <a href="javascript:void(0);" data-action="{{ route('rider.sendemail', ['company_slug' => $companySlug, 'id' => $result['id']]) }}" data-size="md"
            data-title="{{ $result['name'] . ' (' . $result['rider_id'] . ')' }}"
            class="btn btn-outline-warning btn-sm show-modal text-nowrap">
            <i class="fas fa-envelope"></i>&nbsp;Send Email
        </a>
        @endcan
        @can('riders_timeline_create')
        <a href="javascript:void(0);" data-action="{{ route('rider.job_status', ['company_slug' => $companySlug, 'id' => $result['id']]) }}" data-size="md" data-title="Add Timeline" class="btn btn-outline-success btn-sm text-nowrap show-modal">
            <i class="fas fa-chart-bar"></i>&nbsp;Add Timeline
        </a>
        @endcan
    </div>
</div>
@endif