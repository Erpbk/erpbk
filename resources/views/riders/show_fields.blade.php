@extends('riders.view')
@section('page_content')
<div class="card border">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="ti ti-user ti-sm me-1_5 me-2" style=" background: #cadaef;color: #024baa;"></i><b>Personal Information</b></div>
            <button type="button" class="btn btn-sm edit-btn" data-section="personal">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label class="required">Rider ID </label>
                <p>{{$result['rider_id']}}</p>
            </div>
            {{-- <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Courier ID </label>
                <p>{{@$result['courier_id']}}</p>
            </div> --}}
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Rider Name </label>
                <p>{{@$result['name']}}</p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Rider Contact</label>
                <p>{{@$result['personal_contact']}}</p>
            </div>

            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Company Contact</label>
                <p>{{@$result['company_contact']}}</p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Personal Gmail ID </label>
                <p>{{@$result['personal_email']}}</p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Nationality </label>
                <p>{{$rider?->country?->name}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Ethnicity</label>
                <p>{{@$result['ethnicity']}}</p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>DOB </label>
                <p>{{@App\Helpers\General::DateFormat($result['dob'])}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Date of Joining </label>
                <p>{{@App\Helpers\General::DateFormat($result['doj'])}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Passport </label>
                <p>{{@$result['passport']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label @if(strtotime($result['passport_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>Passport Expiry </label>
                <p @if(strtotime($result['passport_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>{{@App\Helpers\General::DateFormat($result['passport_expiry'])}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Other Details</label>
                <p>{{@$rider->other_details}}</p> 
            </div>
        </div>

    </div>
    <!-- Edit Form for Personal Information -->
    <div class="card-body edit-form" id="edit-personal" style="display: none;">
        <form class="section-form" data-section="personal">
            @csrf
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label class="required">Rider ID</label>
                    <input type="text" class="form-control form-control-sm" name="rider_id" value="{{$result['rider_id']}}" readonly>
                </div>
                {{-- <div class="col-md-3 form-group col-3">
                    <label>Courier ID</label>
                    <input type="text" class="form-control form-control-sm" name="courier_id" value="{{@$result['courier_id']}}">
                </div> --}}
                <div class="col-md-3 form-group col-3">
                    <label>Rider Name</label>
                    <input type="text" class="form-control form-control-sm" name="name" value="{{@$result['name']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Rider Contact</label>
                    <input type="text" class="form-control form-control-sm" name="personal_contact" value="{{@$result['personal_contact']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Company Contact</label>
                    <input type="text" class="form-control form-control-sm" name="company_contact" value="{{@$result['company_contact']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Personal Gmail ID</label>
                    <input type="email" class="form-control form-control-sm" name="personal_email" value="{{@$result['personal_email']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Nationality</label>
                    <select class="form-control form-control-sm select2" name="nationality">
                        @foreach($countries as $id => $name)
                        <option value="{{$id}}" {{$result['nationality'] == $id ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Ethnicity</label>
                    <input type="text" class="form-control form-control-sm" name="ethnicity" value="{{@$result['ethnicity']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>DOB</label>
                    <input type="date" class="form-control form-control-sm" name="dob" value="{{@$result['dob']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Date of Joining</label>
                    <input type="date" class="form-control form-control-sm" name="doj" value="{{@$result['doj']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Passport</label>
                    <input type="text" class="form-control form-control-sm" name="passport" value="{{@$result['passport']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Passport Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="passport_expiry" value="{{@$result['passport_expiry']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Other Details</label>
                    <input type="dtext" class="form-control form-control-sm" name="other_details" value="{{@$result['other_details']}}">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                        <i class="ti ti-x me-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="ti ti-note ti-sm me-1_5 me-2" style=" background: #3a3a3c52;color: #3a3a3c;"></i><b>Visa & Registerations</b></div>
            <button type="button" class="btn btn-sm edit-btn" data-section="visa">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Licence No </label>
                <p>{{@$result['license_no']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label @if(strtotime($result['license_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>Licence Expiry </label>
                <p @if(strtotime($result['license_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>{{@App\Helpers\General::DateFormat($result['license_expiry'])}}</p>
            </div>
            <!--col-->
            <div class="col-md-3 form-group col-3">
                <label>Emirate ID </label>
                <p>{{@$result['emirate_id']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label @if(strtotime($result['emirate_exp']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>EID EXP Date </label>
                <p @if(strtotime($result['emirate_exp']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>{{@App\Helpers\General::DateFormat($result['emirate_exp'])}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Road Permit No.</label>
                <p>{{$result['road_permit'] ?? ''}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label @if(strtotime($result['road_permit_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>ROAD PERMIT EXPIRY </label>
                <p @if(strtotime($result['road_permit_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>{{@App\Helpers\General::DateFormat($result['road_permit_expiry'])}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Visa Sponsor</label>
                <p>{{@$result['visa_sponsor']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Occupation on Visa </label>
                <p>{{@$result['visa_occupation']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Visa Status</label>
                <p>{{@$result['visa_status']}}</p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Passport Handover </label>
                <p>{{@$result['passport_handover']}}</p>
            </div>
        </div>
    </div>
    <!-- Edit Form for Visa & Registrations -->
    <div class="card-body edit-form" id="edit-visa" style="display: none;">
        <form class="section-form" data-section="visa">
            @csrf
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Licence No</label>
                    <input type="text" class="form-control form-control-sm" name="license_no" value="{{@$result['license_no']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Licence Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="license_expiry" value="{{@$result['license_expiry']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Emirate ID</label>
                    <input type="text" class="form-control form-control-sm" name="emirate_id" value="{{@$result['emirate_id']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>EID EXP Date</label>
                    <input type="date" class="form-control form-control-sm" name="emirate_exp" value="{{@$result['emirate_exp']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Road Permit</label>
                    <input type="text" class="form-control form-control-sm" name="road_permit" value="{{@$result['road_permit']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Road Permit Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="road_permit_expiry" value="{{@$result['road_permit_expiry']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Visa Sponsor</label>
                    <input type="text" class="form-control form-control-sm" name="visa_sponsor" value="{{@$result['visa_sponsor']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Occupation on Visa</label>
                    <input type="text" class="form-control form-control-sm" name="visa_occupation" value="{{@$result['visa_occupation']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Visa Status</label>
                    <input type="text" class="form-control form-control-sm" name="visa_status" value="{{@$result['visa_status']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Passport Handover</label>
                    <input type="text" class="form-control form-control-sm" name="passport_handover" value="{{@$result['passport_handover']}}">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                        <i class="ti ti-x me-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="card border">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="ti ti-briefcase ti-sm me-1_5 me-2" style=" background: #a002aa38;color: #a002aa;"></i><b>Job Detail</b></div>
            <button type="button" class="btn btn-sm edit-btn" data-section="job">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Project </label>
                <p>{{@$rider->customer->name}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Designation </label>
                <p>{{@$result['designation']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>CDM Deposit ID</label>
                <p>{{@$result['cdm_deposit_id']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Fleet Supervisor </label>
                <p>{{@$result['fleet_supervisor']}}</p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Status </label>
                <p>{{App\Helpers\General::RiderStatus(@$result['status'])}}</p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Salary Model </label>
                <p>{{@$result['salary_model']}}</p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Rider Reference </label>
                <p>{{@$result['rider_reference']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Vendor </label>
                <p>{{@$rider->vendor->name}}</p>

            </div>
            <div class="col-md-3 form-group col-3">
                <label>Recruiter </label>
                <p>{{@$rider->recruiter->name}}</p>
            </div>
        </div>
    </div>
    <!-- Edit Form for Job Detail -->
    <div class="card-body edit-form" id="edit-job" style="display: none;">
        <form class="section-form" data-section="job">
            @csrf
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Project</label>
                    <input type="text" class="form-control form-control-sm" name="project" value="{{@$rider->project->name ?? ''}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Designation</label>
                    <input type="text" class="form-control form-control-sm" name="designation" value="{{@$result['designation']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>CDM Deposit ID</label>
                    <input type="text" class="form-control form-control-sm" name="cdm_deposit_id" value="{{@$result['cdm_deposit_id']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Fleet Supervisor</label>
                    <select class="form-control form-control-sm select2" name="fleet_supervisor">
                        <option value="">Select Fleet Supervisor</option>
                        @php
                        $supervisorRow = DB::table('dropdowns')
                        ->where('label', 'Fleet Supervisor')
                        ->whereNotNull('values')
                        ->first();
                        $fleetSupervisors = [];
                        if ($supervisorRow && $supervisorRow->values) {
                        $fleetSupervisors = json_decode($supervisorRow->values, true);
                        }
                        @endphp
                        @foreach($fleetSupervisors as $supervisor)
                        <option value="{{ $supervisor }}" {{ @$result['fleet_supervisor'] == $supervisor ? 'selected' : '' }}>{{ $supervisor }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Status</label>
                    <select class="form-control form-control-sm select2" name="status">
                        <option value="1" {{@$result['status'] == 1 ? 'selected' : ''}}>Active</option>
                        <option value="2" {{@$result['status'] == 2 ? 'selected' : ''}}>Inactive</option>
                        <option value="3" {{@$result['status'] == 3 ? 'selected' : ''}}>Pending</option>
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Salary Model</label>
                    <input type="text" class="form-control form-control-sm" name="salary_model" value="{{@$result['salary_model']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Rider Reference</label>
                    <input type="text" class="form-control form-control-sm" name="rider_reference" value="{{@$result['rider_reference']}}">
                </div>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Vendor</label>
                <select class="form-control form-control-sm select2" name="VID">
                    @foreach($vendors as $id => $name)
                    <option value="{{$id}}" {{$result['VID'] == $id ? 'selected' : ''}}>{{$name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Recruiter</label>
                <select class="form-control form-control-sm select2" name="recruiter_id">
                    <option value="">Select Recruiter</option>
                    @foreach(DB::table('recruiters')->where('status', 1)->get() as $key => $value)
                    <option value="{{$value->id}}" {{$result['recruiter_id'] == $value->id ? 'selected' : ''}}>{{$value->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                        <i class="ti ti-x me-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="card border">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div><i class="ti ti-user ti-sm me-1_5 me-2" style=" background: #3a3a3c52;color: #3a3a3c;"></i><b>Labor Info</b></div>
            <button type="button" class="btn btn-sm edit-btn" data-section="labor">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Labor Card Number</label>
                <p>{{@$result['labor_card_number']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label @if(strtotime($result['labor_card_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>Labor Card Expiry </label>
                <p @if(strtotime($result['labor_card_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>{{@App\Helpers\General::DateFormat($result['labor_card_expiry'])}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Person Code</label>
                <p>{{@$result['person_code']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Policy No: </label>
                <p>{{@$result['policy_no']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Insurance</label>
                <p>{{@$result['insurance']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label @if(strtotime($result['insurance_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>Insurance Expiry</label>
                <p @if(strtotime($result['insurance_expiry']) <=strtotime(date('Y-m-d'))) style="color:red;" @endif>{{@App\Helpers\General::DateFormat($result['insurance_expiry'])}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>WPS: </label>
                <p>{{@$result['wps']}}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Salary Card:</label>
                <p>{{@$result['c3_card']}}</p>
            </div>
        </div>
    </div>
    <!-- Edit Form for Labor Info -->
    <div class="card-body edit-form" id="edit-labor" style="display: none;">
        <form class="section-form" data-section="labor">
            @csrf
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Labor Card Number</label>
                    <input type="text" class="form-control form-control-sm" name="labor_card_number" value="{{@$result['labor_card_number']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Labor Card Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="labor_card_expiry" value="{{@$result['labor_card_expiry']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Person Code</label>
                    <input type="text" class="form-control form-control-sm" name="person_code" value="{{@$result['person_code']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Policy No:</label>
                    <input type="text" class="form-control form-control-sm" name="policy_no" value="{{@$result['policy_no']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Insurance</label>
                    <input type="text" class="form-control form-control-sm" name="insurance" value="{{@$result['insurance']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Insurance Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="insurance_expiry" value="{{@$result['insurance_expiry']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>WPS:</label>
                    <input type="text" class="form-control form-control-sm" name="wps" value="{{@$result['wps']}}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Salary Card:</label>
                    {!! Form::select('c3_card',
                    Common::Dropdowns('c3-card'),
                    null,
                    [
                    'class' => 'form-select',
                    'placeholder' => 'Select Sallary Type'
                    ]) !!}
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                        <i class="ti ti-x me-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
{{-- <div class="row m-1 border">
  <div class="col-md-4 border-right border-bottom" style="height: 45px;">
      <b>Name</b><br/> {{@$rider->name}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Rider ID</b><br /> {{@$rider->rider_id}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Contact</b><br /> {{@$rider->personal_contact}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Rider ID</b><br /> {{@$rider->rider_id}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Vendor</b><br /> {{@$rider->vendor->name}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Company Contact</b><br /> {{@$rider->company_contact}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Nationality</b><br /> {{@$rider->nationality}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Personal Email ID</b><br /> {{@$rider->personal_email}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Email ID</b><br /> {{@$rider->email}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Visa Sponsor</b><br /> {{@$rider->visa_sponsor}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Occupation On Visa</b><br /> {{@$rider->visa_occupation}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Visa Status</b><br /> {{@$rider->visa_status}}
</div>

<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>CDM Deposit ID</b><br /> {{@$rider->cdm_deposit_id}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Mashreq ID</b><br /> {{@$rider->mashreq_id}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Date of Joining</b><br /> {{@$rider->doj}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Project</b><br /> {{@$rider->project->name}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Designation</b><br /> {{@$rider->designation}}
</div>

<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Ethnicity</b><br /> {{@$rider->ethnicity}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>DOB</b><br /> {{@$rider->dob}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Branded Plate Number</b><br /> {{@$rider->branded_plate_no}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Fleet Supervisor</b><br /> {{@$rider->fleet_supervisor}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Passport Handover</b><br /> {{@$rider->passport_handover}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Status</b><br /> {{App\Helpers\General::RiderStatus(@$rider->status)}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Emirate (Hub)</b><br /> {{@$rider->emirate_hub}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Emirate ID</b><br /> {{@$rider->emirate_id}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Licence No</b><br /> {{@$rider->license_no}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>Passport</b><br /> {{@$rider->passport}}
</div>

<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>WPS</b><br /> {{@$rider->wps}}
</div>
<div class="col-md-4 border-right border-bottom" style="height: 45px;">
    <b>C3 Card</b><br /> {{@$rider->c3_card}}
</div>
<div class="col-md-12">
    <b>Other Details</b><br /> {{@$rider->other_details}}
</div>

</div>
--}}


<style>
    .edit-form {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        border-radius: 23px;
    }

    .edit-btn {
        font-size: 12px;
        padding: 4px 1px;
        border-radius: 70px;
    }

    .section-form .form-control-sm {
        font-size: 12px;
    }

    .card-header .d-flex {
        align-items: center;
    }

    /* Select2 styling for small forms */
    .edit-form .select2-container .select2-selection--single {
        height: 31px;
        font-size: 12px;
    }

    .edit-form .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 29px;
        padding-left: 8px;
        padding-right: 20px;
    }

    .edit-form .select2-container .select2-selection--single .select2-selection__arrow {
        height: 29px;
        right: 3px;
    }

    .edit-form .select2-dropdown {
        font-size: 12px;
    }

    .edit-form .select2-container--bootstrap4 .select2-selection--single {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
</style>

@endsection
@section('page-script')
<script>
    $(document).ready(function() {
        // Initialize Select2 for all select elements
        function initializeSelect2() {
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Select an option'
            });
        }

        // Handle edit button clicks
        $('.edit-btn').click(function() {
            const section = $(this).data('section');
            const editForm = $('#edit-' + section);
            const displaySection = $(this).closest('.card').find('.card-body').first();

            // Hide display section and show edit form
            displaySection.hide();
            editForm.show();

            // Initialize Select2 for this section's dropdowns
            editForm.find('.select2').select2({
                width: '100%',
                placeholder: 'Select an option'
            });

            // Change button text to Cancel
            $(this).html('<i class="ti ti-x me-1"></i>').removeClass('btn-primary').addClass('btn-secondary').addClass('cancel-edit');
        });

        // Handle cancel button clicks
        $(document).on('click', '.cancel-edit', function() {
            const section = $(this).data('section') || $(this).closest('form').data('section');
            const editForm = $('#edit-' + section);
            const displaySection = $(this).closest('.card').find('.card-body').first();
            const editBtn = $(this).closest('.card').find('.edit-btn');

            // Note: Select2 instances will be re-initialized when the form is shown again

            // Hide edit form and show display section
            editForm.hide();
            displaySection.show();

            // Reset button
            editBtn.html('<i class="ti ti-edit me-1"></i>').removeClass('btn-secondary cancel-edit').addClass('btn-primary');
        });

        // Handle form submissions
        $('.section-form').submit(function(e) {
            e.preventDefault();

            const form = $(this);
            const section = form.data('section');
            const formData = new FormData(this);
            formData.append('section', section);

            // Add loading state
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

            $.ajax({
                url: '{{ route("riders.updateSection", $rider->id) }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        toastr.success(response.message);

                        // Reload the page to show updated data
                        location.reload();
                    } else {
                        toastr.error('Error updating ' + section + ' information');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMessage = 'Validation errors:\n';
                        Object.keys(errors).forEach(function(key) {
                            errorMessage += errors[key][0] + '\n';
                        });
                        toastr.error(errorMessage);
                    } else {
                        toastr.error('Error updating ' + section + ' information');
                    }
                },
                complete: function() {
                    // Reset button state
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
@endsection