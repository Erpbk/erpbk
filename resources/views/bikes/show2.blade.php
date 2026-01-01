@extends('bikes.view')

@section('page_content')
<div class="card mb-4" style="border: 1px solid #dee2e6;">

  <div class="card-body">
    <!-- Document Header -->
    <div class="document-header mb-4">
      <div class="row align-items-center">
        <div class="col-md-10">
          <h3 style="color: #1d3557; font-weight: 700; margin-bottom: 5px;">Vehicle License</h3>
          <p style="color: #6c757d; font-size: 0.9rem;">Official vehicle registration details</p>
        </div>
        <div class="col-md-2 text-end">
          @php
            $authorized = false;
            if(auth()->user()->can('bike_document')){
              $authorized = true;
            }
          @endphp
          @if($mulkiyaFile)
            <div class="document-id">
                <a href="{{ url('storage2/' . $mulkiyaFile->type . '/'.$mulkiyaFile->type_id.'/'.$mulkiyaFile->file_name)}}" target="_blank">
                  <i class="ti ti-download"></i>Mulkiya
                </a>
            </div>
          @elseif($authorized)
            <a class="btn btn-primary show-modal action-btn"
             href="javascript:void(0);" 
             data-action="{{ route('files.create',['type_id'=> $bikes->id,'type'=> 'bike', 'suggested_name' => 'Mulkiya']) }}" data-size="sm" data-title="Upload File">
                Upload Mulkiya
            </a>
          @else
            <div class="document-id">
              No Mulkiya Found
            </div>     
          @endif
        </div>
      </div>
    </div>
    
    <!-- Primary Information -->
    <div class="row mb-4">
      <div class="col-md-6">
        <table class="info-table">
          <tr>
            <td class="table-label">Current Project:</td>
            <td class="table-value">{{ $bikes->customer->name ?? 'N/A'}}</td>
          </tr>
          <tr>
            <td class="table-label">Traffic File Number:</td>
            <td class="table-value">{{ $bikes->traffic_file_number ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="table-label">Insurance Company:</td>
            <td class="table-value">{{ $bikes->insurance_co ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="table-label" @if(strtotime($bikes->insurance_expiry) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>Insurance Expiry:</td>
            <td class="table-value" @if(strtotime($bikes->insurance_expiry) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>{{ $bikes->insurance_expiry ?? 'N/A' }}</td>
          </tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="info-table">
          <tr>
            <td class="table-label">Registration Date:</td>
            <td class="table-value">{{ $bikes->registration_date ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="table-label">Leasing Company:</td>
            <td class="table-value">{{ DB::table('leasing_companies')->where('id', $bikes->company)->first()->name ?? 'N/A' }}</td>
          </tr>
            <td class="table-label">Policy Number:</td>
            <td class="table-value">{{ $bikes->policy_no ?? 'N/A' }}</td>
          </tr>
        </table>
      </div>
    </div>
    
    <!-- Vehicle Specifications -->
    <div class="spec-section">
      <h6 class="spec-title">Vehicle Specifications</h6>
      <div class="row">
        <div class="col-md-3 col-6 mb-3">
          <div class="spec-item">
            <div class="spec-label">Model</div>
            <div class="spec-value">{{ $bikes->model ?? 'N/A' }}</div>
          </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
          <div class="spec-item">
            <div class="spec-label">Color</div>
            <div class="spec-value">{{ $bikes->color ?? 'N/A' }}</div>
          </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
          <div class="spec-item">
            <div class="spec-label">Type</div>
            <div class="spec-value">{{ $bikes->model_type ?? 'N/A' }}</div>
          </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
          <div class="spec-item">
            <div class="spec-label">Emirates</div>
            <div class="spec-value">{{ $bikes->emirates ?? 'N/A' }}</div>
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <div class="spec-item">
            <div class="spec-label">Engine Number</div>
            <div class="spec-value code">{{ $bikes->engine ?? 'N/A' }}</div>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <div class="spec-item">
            <div class="spec-label">Chassis Number</div>
            <div class="spec-value code">{{ $bikes->chassis_number ?? 'N/A' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.document-id {
  background: #f8f9fa;
  text-align: center;
  font-size: 0.9rem;
  font-weight: 600;
  padding: 8px 12px;
  border-radius: 4px;
  border-left: 3px solid #1d3557;
}

.info-table {
  width: 100%;
}

.info-table tr {
  border-bottom: 1px solid #f1f3f5;
}

.table-label {
  padding: 10px 0;
  color: #495057;
  font-weight: 600;
  width: 40%;
  font-size: 0.9rem;
}

.table-value {
  padding: 10px 0 10px 15px;
  color: #212529;
  font-weight: 500;
}

.spec-section {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-top: 20px;
}

.spec-title {
  color: #1d3557;
  font-weight: 700;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #dee2e6;
}

.spec-item {
  margin-bottom: 15px;
}

.spec-label {
  font-size: 0.8rem;
  color: #6c757d;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 5px;
}

.spec-value {
  font-size: 1rem;
  color: #212529;
  font-weight: 600;
}

.spec-value.code {
  font-family: monospace;
  background: white;
  padding: 8px;
  border-radius: 4px;
  border: 1px solid #dee2e6;
}

.spec-value.highlight {
  color: #1d3557;
  font-size: 1.2rem;
  font-weight: 700;
}
</style>

@endsection