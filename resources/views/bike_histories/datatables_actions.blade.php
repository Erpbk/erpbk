{!! Form::open(['route' => ['bikeHistories.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
  @can('bike_document')
  <a href="javascript:void();" data-action="{{route('bike_contract_upload', $id)}}" data-size="md" data-title="Bike Contract" class="btn btn-warning btn-sm show-modal mr-1"><i class="fas fa-file mx-2"></i> Contract</a>
  @endcan
</div>
{!! Form::close() !!}
