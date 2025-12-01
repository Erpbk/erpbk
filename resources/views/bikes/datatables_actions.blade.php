{!! Form::open(['route' => ['bikes.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
<div class="dropdown">
  <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
  </button>
  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">

    {{-- <a href="{{ route('bikes.show', $id) }}" class='btn btn-default btn-xs'>
    <i class="fa fa-eye"></i>
    </a> --}}

    <a href="javascript:void(0);" data-size="lg" data-title="Assign Rider to Bike # {{$plate}}" data-action="{{ route('bikes.assign_rider', $id) }}" class='dropdown-item waves-effect btn-sm show-modal'>
      <i class="fa fa-biking"></i>Assign Rider
    </a>
    <a href="{{ route('bikeHistories.index', ['bike_id'=>$id]) }}" class='dropdown-item waves-effect'>
      <i class="fa fa-list-check"></i>History
    </a>
    @can('bike_document')
    <a href="javascript:void(0);" data-size="sm" data-title="Upload file for Bike # {{$plate}}" data-action="{{ route('files.create',['type_id'=>$id,'type'=>'bike']) }}" class='dropdown-item waves-effect btn-sm show-modal'>
      <i class="fa fa-file-upload"></i>Upload File
    </a>
    @endcan
    <a href="{{ route('files.index',['type_id'=>$id,'type'=>'bike']) }}" class='dropdown-item waves-effect'>
      <i class="fa fa-file-lines"></i>Files
    </a>
    @can('item_edit')
    <a href="javascript:void(0);" data-size="xl" data-title="Update Bike" data-action="{{ route('bikes.edit', $id) }}" class='dropdown-item waves-effect show-modal'>
      <i class="fa fa-edit"></i>Edit
    </a>
    @endcan

    @can('item_delete')
    {!! Form::button('<i class="fa fa-trash"></i> Delete', [
    'type' => 'submit',
    'class' => 'dropdown-item waves-effect',
    'onclick' => 'return confirm("Are you sure?")'

    ]) !!}
    @endcan

  </div>
  {!! Form::close() !!}