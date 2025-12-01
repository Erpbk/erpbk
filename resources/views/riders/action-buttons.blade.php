<!-- Action Buttons Component -->
<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-start">
            @isset($result)
            @can('rider_edit')
            <a href="{{route('riders.edit', $result['id'])}}" class="btn btn-outline-primary btn-sm waves-effect waves-light me-1"><i class="fa fa-edit"></i>&nbsp;Edit</a>
            @endcan
            @can('email_create')
            <a href="javascript:void();" data-action="{{route('rider.sendemail', $result['id'])}}" data-size="md"
                data-title="{{$result['name'] . ' (' . $result['rider_id'] }}')" class="btn btn-outline-warning btn-sm show-modal text-nowrap"><i class="fas fa-envelope"></i>&nbsp;Send Email</a>
            @endcan
            @can('timeline_create')
            <a href="javascript:void(0);" data-action="{{url('riders/job_status/' . $result['id']) }}" data-size="md" data-title="Add Timeline" class="btn btn-outline-success btn-sm text-nowrap show-modal mx-1"><i class="fas fa-chart-bar"></i>&nbsp;Add Timeline</a>
            @endcan
            @endisset
        </div>
    </div>
</div>