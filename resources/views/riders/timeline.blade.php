@extends('riders.view')

@section('page_content')

<div class="card card-action mb-6">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-chart-bar ti-lg text-body me-4"></i>Timeline</h5>
  </div>
  <div class="card-body pt-3 px-5">
    <ul class="timeline mb-0">
      @isset($job_status)
      @foreach($job_status as $row)
      <li class="timeline-item timeline-item-transparent">
        <span class="timeline-point timeline-point-primary"></span>
        <div class="timeline-event">
          <div class="timeline-header mb-3">
            <h6 class="mb-0"><a href="#">{{$row->user->name}}</a> added reason {{-- changed status to @isset($row->job_status)<span class="badge bg-label-success">{{App\Helpers\General::JobStatus($row->job_status)}}</span> @endisset--}}</h6>
            <small class="text-muted">{{$row->created_at->diffForHumans()}}<br />{{App\Helpers\General::DateTimeFormat($row->created_at)}}</small>
          </div>
          <p class="mb-2">
            {{$row->reason}}
          </p>
          {{-- <div class="d-flex align-items-center mb-2">
            <div class="badge bg-lighter rounded d-flex align-items-center">
              <img src="../../assets//img/icons/misc/pdf.png" alt="img" width="15" class="me-2">
              <span class="h6 mb-0 text-body">invoices.pdf</span>
            </div>
          </div> --}}
        </div>
      </li>
      @endforeach
      @endisset

    </ul>
  </div>
</div>

@endsection