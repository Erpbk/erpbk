@extends('bikes.view')

@section('page_content')

<div class="card card-action mb-1">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0">Bike Detail</h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3 form-group col-3">
        <label>Bike Code:</label>
        <p>{{ $bikes->bike_code ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Plate #:</label>
        <p>{{ $bikes->plate ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Chassis #:</label>
        <p>{{ $bikes->chassis_number ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Color</label>
        <p>{{ $bikes->color ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Model #:</label>
        <p>{{ $bikes->model ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Model Type:</label>
        <p>{{ $bikes->model_type ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Engine #:</label>
        <p>{{ $bikes->engine ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Traffic File #:</label>
        <p>{{ $bikes->traffic_file_number ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Emirates:</label>
        <p>{{ $bikes->emirates ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Company:</label>
        <p>{{ DB::table('leasing_companies')->where('id' , $bikes->company)->first()->name ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Registration Date:</label>
        <p>{{ \Carbon\Carbon::parse($bikes->registration_date)->format('d M Y') ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Expiry Date:</label>
        <p>{{ \Carbon\Carbon::parse($bikes->expiry_date)->format('d M Y') ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Insurance Expiry:</label>
        <p>{{ \Carbon\Carbon::parse($bikes->insurance_expiry)->format('d M Y') ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Insurance Co:</label>
        <p>{{ $bikes->insurance_co ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Policy No:</label>
        <p>{{ $bikes->policy_no ?? 'N/A' }}</p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Contract No:</label>
        <p>{{ $bikes->contract_number ?? 'N/A' }}</p>
      </div>
    </div>
  </div>
</div>
@endsection