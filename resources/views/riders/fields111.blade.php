@isset($result)
                            {!! Form::model($result, ['route' => ['riders.store'], 'id'=>'formajax','class'=>'myform']) !!}
                            <input type="hidden" name="id" value="{{$result['id']}}"/>
                            <input type="hidden" name="redirect_to" id="redirect_to" value="{{url('rider')}}" />

                            @else
                            {!! Form::open(['route' => 'riders.store', 'id'=>'formajax']) !!}
                            <input type="hidden" name="id" value="0">
                            {{-- <input type="hidden" name="edit_redirect" id="edit_redirect" value="1"> --}}
                            <input type="hidden" name="redirect_to" id="redirect_to" value="{{url('rider')}}" />


                            @endisset
                            @csrf



                                <div class="row">
                                    <div class="col-md-3 form-group">

                                        <label class="required">Rider ID <span style="color:red;">*</span></label>

                                        {!! Form::text('rider_id', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_id_edit') ? 'readonly' : null]) !!}

                                    </div>
                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>Rider Name <span style="color:red;">*</span></label>
                                        {!! Form::text('name', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_name_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>Rider Contact</label>
                                        {!! Form::text('personal_contact', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255]) !!}
                                    </div>
                                    {{-- <div class="col-md-3 form-group">
                                        <label>Vendor <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm @can('rider_vendor_edit') select2 @else select-readonly @endcan" name="VID">
                                            <option value="">Select</option>
                                            {!! \App\Models\Vendor::dropdown(@$result['VID']) !!}
                                        </select>
                                    </div> --}}
                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>Company Contact</label>
                                        {!! Form::text('company_contact', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_company_contact_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>Personal Gmail ID  <span style="color:red;">*</span></label>
                                        {!! Form::text('personal_email', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_personal_gmail_id_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <!--col-->
                                    {{-- <div class="col-md-3 form-group">
                                        <label>Email</label>
                                        <input type="text" class="form-control form-control-sm" name="email" placeholder="Person Email">
                                    </div> --}}
                                    <!--col-->
                                   {{--  <div class="col-md-3 form-group">
                                        <label>Nationality <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm select2" name="nationality">
                                            <option value="">Select</option>
                                            {!! \App\Models\Country::dropdown(@$result['nationality']) !!}
                                        </select>
                                    </div> --}}
                                    <div class="col-md-3 form-group">
                                        <label>Ethnicity</label>
                                        <select type="text" class="form-control form-control-sm select2" name="ethnicity">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::get_Ethnicity(@$result['ethnicity']) !!}

                                        </select>
                                    </div>
                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>DOB <span style="color:red;">*</span></label>
                                        {!! Form::date('dob', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Date of Joining <span style="color:red;">*</span></label>
                                        {!! Form::date('doj', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_date_of_joining_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    {{-- <div class="col-md-3 form-group">
                                        <label>Project <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm @can('rider_customer_edit') select2 @else select-readonly @endcan" name="PID">
                                            <option value="">Select</option>
                                            {!! \App\Models\Customers::dropdown(@$result['PID']) !!}
                                        </select>
                                    </div> --}}
                                    <div class="col-md-3 form-group">
                                        <label>Designation <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm @can('rider_designation_edit') select2 @else select-readonly @endcan" name="designation">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::Designations(@$result['designation']) !!}
                                        </select>

                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label>Visa Sponsor</label>
                                        {!! Form::text('visa_sponsor', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_visa_sponsor_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Occupation on Visa <span style="color:red;">*</span></label>
                                        {!! Form::text('visa_occupation', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_occupation_on_visa_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Visa Status</label>
                                        <select class="form-control form-control-sm @can('rider_visa_status_edit') select2 @else select-readonly @endcan" name="visa_status">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::VisaStatus(@$result['visa_status']) !!}
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Person Code</label>
                                        {!! Form::text('person_code', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_person_code_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Labor Card Number</label>
                                        {!! Form::text('labor_card_number', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_labor_card_number_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Labor Card Expiry </label>
                                        {!! Form::date('labor_card_expiry', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_labor_card_expiry_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Insurance</label>
                                        <select class="form-control form-control-sm @can('rider_insurance_edit') select2 @else select-readonly @endcan" name="insurance">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::Insurance(@$result['insurance']) !!}
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Insurance Expiry</label>
                                        {!! Form::date('insurance_expiry', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_insurance_expiry_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Policy No.</label>
                                        {!! Form::text('policy_no', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_policy_no_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <!--col-->
                                    {{-- <div class="col-md-3 form-group">
                                        <label>NF DID</label>
                                        <input type="text" class="form-control form-control-sm" name="NFDID" placeholder="NF DID">
                                    </div> --}}
                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>CDM Deposit ID</label>
                                        {!! Form::text('cdm_deposit_id', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255]) !!}
                                    </div>
                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>Mashreq ID</label>
                                        {!! Form::text('mashreq_id', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255]) !!}
                                    </div>
                                    <!--col-->

                                    <!--col-->

                                    <!--col-->

                                    <!--col-->
                                    {{-- <div class="col-md-3 form-group">
                                        <label>Dept</label>
                                        <input type="text" class="form-control form-control-sm dat" name="DEPT" placeholder="Dept">
                                    </div> --}}
                                    <!--col-->

                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>Branded Plate Number</label>
                                        {!! Form::text('branded_plate_no', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255]) !!}
                                    </div>
                                    <!--col-->
                                   {{--  <div class="col-md-3 form-group">
                                        <label>Fleet Supervisor <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm select2" name="fleet_supervisor">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::get_supervisor(@$result['fleet_supervisor']) !!}
                                        </select>
                                    </div> --}}

                                    {{-- <div class="col-md-3 form-group">
                                        <label>Status <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm select2" name="status">

                                            @foreach(App\Helpers\General::RiderStatus() as $key=>$value)
                                            <option value="{{$key}}"@if(@$result['status']==$key)selected @endif>{{$value}}</option>
                                            @endforeach


                                        </select>
                                    </div> --}}
                                    <div class="col-md-3 form-group">
                                        <label>Salary Model <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm @can('rider_salary_model_edit') select2 @else select-readonly @endcan" name="salary_model">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::SalaryModel(@$result['salary_model']) !!}
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Rider Reference <span style="color:red;">*</span></label>
                                        {!! Form::text('rider_reference', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255]) !!}
                                    </div>
                                </div>
                                <div class="row bg-light mb-1">
                                    <div class="col-md-3 form-group">
                                        <label>Emirate (Hub) <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm @can('rider_emirate_hub_edit') select2 @else select-readonly @endcan" name="emirate_hub">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::EmiratesHub(@$result['emirate_hub']) !!}
                                        </select>

                                    </div>
                                    <!--col-->
                                    <div class="col-md-3 form-group">
                                        <label>Emirate ID <span style="color:red;">*</span></label>
                                        {!! Form::text('emirate_id', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_emirate_id_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>EID EXP Date <span style="color:red;">*</span></label>
                                        {!! Form::date('emirate_exp', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_eid_expiry_date_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Licence No <span style="color:red;">*</span></label>
                                        {!! Form::text('license_no', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_license_no_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Licence Expiry <span style="color:red;">*</span></label>
                                        {!! Form::date('license_expiry', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_license_expiry_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Passport <span style="color:red;">*</span></label>
                                        {!! Form::text('passport', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_passport_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Passport Expiry <span style="color:red;">*</span></label>
                                        {!! Form::date('passport_expiry', null, ['class' => 'form-control form-control-sm', 'maxlength' => 255,'readonly'=>auth()->user()->cannot('rider_passport_expiry_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Passport Handover <span style="color:red;">*</span></label>
                                        <select class="form-control form-control-sm @can('rider_passport_handover_edit') select2 @else select-readonly @endcan" name="passport_handover">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::get_passport_handover(@$result['passport_handover']) !!}
                                        </select>
                                    </div>
                                    {{-- <div class="col-md-3 form-group">
                                        <label>NOON No. </label>
                                        <input type="text" class="form-control form-control-sm" name="noon_no" placeholder="Noon No.">
                                    </div> --}}
                                    <div class="col-md-3 form-group">
                                        <label>WPS</label>
                                        <select class="form-control form-control-sm @can('rider_wps_edit') select2 @else select-readonly @endcan" name="wps">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::WPS(@$result['wps']) !!}
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>C3 Card</label>
                                        <select class="form-control form-control-sm @can('rider_c3_card_edit') select2 @else select-readonly @endcan" name="c3_card">
                                            <option value="">Select</option>
                                            {!! App\Helpers\General::C3Card(@$result['c3_card']) !!}
                                        </select>
                                    </div>

                                    </div>

                                <div class="row">


                                    <div class="col-md-12 form-group">
                                        <label>Other Details</label>
                                        {!! Form::textarea('other_details', null, ['class' => 'form-control form-control-sm','readonly'=>auth()->user()->cannot('rider_other_details_edit') ? 'readonly' : null]) !!}
                                    </div>
                                    <!--col-->
                                </div>


                                <div class="row pr-5 pl-5" >
                                    @can('rider_assign_price_edit')
                                    <h3>Assign Price</h3>
                                    <table  class="table" style="border-radius:10px;">
                    <thead>
                        <tr class=" bg-light">
                            <td>Select Item</td>
                            <td>Enter Price</td>
                            <td>Action</td>
                        </tr>
                    </thead>
                    <tbody>

                        <tr class=" bg-light">
                            <td class="col-sm-4">

                                <select name="item_id" class="form-control form-control-sm select2" id="item_id"><option value="0">Select Item</option>
                                    @php
                                        $items = \App\Models\Item::all();
                                    @endphp
                                  @foreach($items as $item)
                                        <option value="{{$item->id}}">{{$item->item_name.' - '.$item->pirce}}</option>
                                   @endforeach
                               </select>
                            </td>
                            <td class="col-sm-4">
                                <label>Price: </label>
                                    <input type="number" step="any" name="item_price" id="item_price" />
                            </td>
                            <td >
                                <input type="button" class="btn btn-lg btn-dark btn-sm btn-block " style="width: 200px;background:#000;" id="addrow" value="Add Item" />
                            </td>

                            {{-- <td class="col-sm-2"><input type="button" class="ibtnDel btn btn-md btn-danger btn-xs"  value="Delete"></td> --}}
                        </tr>
                    </tbody>

                </table>
                <table id="myTable" class="table order-list2">
                    @isset($rider_items)
                        @foreach($rider_items as $item)
                        <tr>
                            <td width="250"><label>{{@$item->item->item_name }}(Price: {{@$item->item->pirce}})</label></td>
                            <td width="130"><input type="number" name="items[{{@$item->item->id}}]" id="item-{{@$item->tem->id}}" value="{{$item->price}}" step="any" class="form-control form-control-sm" /></td>

                            <td width="300"><input type="button" class="ibtnDel btn btn-md btn-xs btn-danger "  value="Delete"></td>
                        </tr>
                        @endforeach
                    @endisset
                </table>
                @endcan

                                      {{--   @php
                                            $items = \App\Models\Item::all();

                                        @endphp
                                        @foreach ($items as $item)
                                        @php
                                             /* $rider_item = \App\Models\RiderItemPrice::where('item_id',$item->id)
                                             ->where('RID',$); */
                                        @endphp

                                            <div class="col-3 form-group">
                                                <label>{{$item->item_name}}(Price: {{$item->pirce}})</label>
                                            <input type="number" name="items[{{$item->id}}]" id="item-{{$item->id}}" step="any" class="form-control form-control-sm" />
                                            </div>
                                        @endforeach
                                        </div> --}}



                                <button type="submit" class="btn btn-primary pull-right btn-md">Save Information</button>
                                {{-- <button class="btn btn-primary btn-sm loader" type="button" disabled style="display: none">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Saving...
                                </button> --}}

                            </form>
                        </div>
