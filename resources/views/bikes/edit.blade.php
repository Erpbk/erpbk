@extends('bikes.view')

@section('page_content')

<div class="card card-action mb-1">
  <div class="card-header align-items-center bg-primary">
    <h5 class="card-action-title mb-0 text-white">Bike Detail</h5>
  </div>
  {!! Form::model($bikes, ['route' => ['bikes.update', $bikes->id], 'method' => 'patch','id'=>'formajax']) !!}


  <input type="hidden" name="updated_by" value="{{ Auth::user()->id }}">
  <div class="card-body">
    <div class="row">
      <div class="col-md-3 form-group col-3">
        <label>Select Vehicle Model</label>
        <select class="form-control select2" name="vehicle_type" id="vehicle_type">
          <option value="">Select Model</option>
          @foreach(DB::table('vehicle_models')->where('status', 1)->get() as $model)
          <option value="{{ $model->id }}" @if($bikes->vehicle_type == $model->id) selected @endif>{{ $model->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('bike_code', 'Bike Code:') !!}
        {!! Form::text('bike_code', $bikes->bike_code ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
      </div>
      <div class="col-md-3 form-group col-3">
        {!! Form::label('plate', 'Number Plate:',['class'=>'required']) !!}
        {!! Form::text('plate', $bikes->plate ?? ' ', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('chassis_number', 'Chassis Number:',['class'=>'required']) !!}
        {!! Form::text('chassis_number', $bikes->chassis_number ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('engine', 'Engine:',['class'=>'required']) !!}
        {!! Form::text('engine', $bikes->engine ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
      </div>
      <div class="col-md-3 form-group col-3">
        {!! Form::label('color', 'Color:',['class'=>'required']) !!}
        
        @php
        $colors = DB::table('bikes')
            ->whereNotNull('color')
            ->select('color')
            ->distinct()
            ->pluck('color');
        @endphp
        
        {!! Form::text('color', $bikes->color ?? '', [
            'class' => 'form-control',
            'list' => 'colorOptions',
            'autocomplete' => 'off'
        ]) !!}
        
        <datalist id="colorOptions">
            @foreach($colors as $color)
                <option value="{{ $color }}">
            @endforeach
        </datalist>
      </div>
      <div class="col-md-3 form-group col-3">
        {!! Form::label('model', 'Model:',['class'=>'required']) !!}
        {!! Form::text('model', $bikes->model ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('model_type', 'Model Type:',['class'=>'required']) !!}
        @php
          $modelType = DB::table('bikes')
              ->whereNotNull('model_type')
              ->select('model_type')
              ->distinct()
              ->pluck('model_type');
        @endphp
        {!! Form::text('model_type', $bikes->model_type ?? '', ['class' => 'form-control', 'list' => 'modelTypeOptions', 'autocomplete' => 'off']) !!}
        <datalist id="modelTypeOptions">
            @foreach($modelType as $type)
                <option value="{{ $type }}">
            @endforeach
        </datalist>
      </div>
      <div class="col-md-3 form-group col-3">
        @php
        $selectedCompany = $bikes && $bikes->company ? $bikes->company : null;
        @endphp

        {!! Form::label('company', 'Leasing Company:',['class'=>'required']) !!}
        {!! Form::select(
        'company',
        \App\Models\LeasingCompanies::dropdown(),
        $selectedCompany,
        ['class' => 'form-control select2', 'required']
        ) !!}
      </div>
      <div class="col-md-3 form-group col-3">
        {!! Form::label('warehouse', 'Location:') !!}
        {!! Form::text('warehouse', $bikes->warehouse ?? '', ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]) !!}
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('traffic_file_number', 'Traffic File Number:') !!}
        {!! Form::text('traffic_file_number', $bikes->traffic_file_number ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
      </div>
      <div class="col-md-3 form-group col-3">
        {!! Form::label('emirate_hub', 'Emirate Hub:', ['class' => 'required']) !!}
        {!! Form::select(
        'emirates',
        Common::Dropdowns('emirates-hub'),
        old('emirates', $bikes->emirates ?? ''), // ✅ selected value
        ['class' => 'form-select select2','required', 'placeholder' => 'Select Emirate']
        ) !!}
      </div>
      <div class="col-md-3 form-group col-3">
        {!! Form::label('registration_date', 'Registration Date:') !!}
        {!! Form::date('registration_date', \Carbon\Carbon::parse($bikes->registration_date)->format('d M Y') ?? '', ['class' => 'form-control','id'=>'registration_date']) !!}
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('expiry_date', 'Expiry Date:') !!}
        {!! Form::date('expiry_date', \Carbon\Carbon::parse($bikes->expiry_date)->format('d M Y') ?? '', ['class' => 'form-control','id'=>'expiry_date']) !!}
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('insurance_expiry', 'Insurance Expiry:') !!}
        {!! Form::date('insurance_expiry', \Carbon\Carbon::parse($bikes->insurance_expiry)->format('d M Y') ?? '', ['class' => 'form-control','id'=>'insurance_expiry']) !!}
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('insurance_co', 'Insurance Co:') !!}
        @php
        $insuranceCo = DB::table('bikes')
            ->whereNotNull('insurance_co')
            ->select('insurance_co')
            ->distinct()
            ->pluck('insurance_co');
        @endphp
        {!! Form::text('insurance_co', $bikes->insurance_co ?? '', ['class' => 'form-control', 'list' => 'insuranceCoOptions', 'autocomplete' => 'off']) !!}
      ` <datalist id="insuranceCoOptions">
          @foreach($insuranceCo as $company)
              <option value="{{ $company }}">
          @endforeach 
      </datalist>
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        {!! Form::label('policy_no', 'Policy No:') !!}
        {!! Form::text('policy_no', $bikes->policy_no ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
      </div>
      <div class="form-group col-sm-3">
        {!! Form::label('customer_id', 'Project:',['class'=>'required']) !!}
        {!! Form::select('customer_id',App\Models\Customers::dropdown(),null,
        ['class' => 'form-select select2', 'required']) !!}
      </div>
      <div class="col-md-12 form-group col-12">
        {!! Form::label('notes', 'Notes:') !!}
        {!! Form::textarea('notes', $bikes->notes ?? '', ['class' => 'form-control', 'rows' => 3]) !!}
      </div>
      <div class="form-group col-sm-6 mt-3 mb-3">
        <label>Status</label>
        <div class="form-check">
          <input type="hidden" name="status" value="2" />
          <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($bikes) @if($bikes->status == 1) checked @endif @else checked @endisset/>
          <label for="status" class="pt-0">Is Active</label>
        </div>
      </div>
      <div class="form-group col-sm-12 mt-3 text-end">
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </div>
  </div>
  {!! Form::close() !!}
</div>
@endsection
@section('page-script')

<script>
  $(document).ready(function() {
    function toggleCyclistFields() {
      let selectedText = $("#vehicle_type option:selected").text().toLowerCase();

      if (selectedText === "cyclist") {
        $(".hide-if-cyclist").hide();
      } else {
        $(".hide-if-cyclist").show();
      }
    }

    // Run on page load
    toggleCyclistFields();

    // Run when vehicle type changes
    $("#vehicle_type").change(function() {
      toggleCyclistFields();
    });
  });
</script>
@endsection