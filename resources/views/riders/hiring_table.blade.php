@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Name" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending" >Rider Name</th>
         <th title="Contact" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Contact: activate to sort column ascending" >Contact</th>
         <th title="Contact" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Contact: activate to sort column ascending" >WhatsApp Contact</th>
         <th title="Fleet Supv" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Fleet Supv: activate to sort column ascending" >Fleet Supv</th>
         <th title="Status" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending" >Stay</th>
         <th title="Shift" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Shift: activate to sort column ascending" >Nationality</th>
         <th title="Shift" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Shift: activate to sort column ascending" >Note</th>
         <th title="Shift" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Shift: activate to sort column ascending" >Created By</th>
         <th title="Shift" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Shift: activate to sort column ascending" >Updated By</th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending" >
            <a data-bs-toggle="modal" data-bs-target="#searchModal"href="javascript:void(0);" > <i class="fa fa-search"></i></a> </th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);" > <i class="fa fa-filter"></i></a> </th>
      </tr>
   </thead>
   <tbody >
      @foreach($data as $r)
         <tr class="text-center">
            <td><a href="{{ route('riders.show', $r->id) }}">{{ $r->name }}</a><br/></td>
            <td>
               @if ($r->contact)
                  <a href="tel:{{ $r->contact }}" target="_blank" class="text-primary">
                  <i class="fa fa-phone"></i>{{ $r->contact }}
               </a>
               @endif
            </td>
            <td>
               @if ($r->whatsapp_contact)
               <a href="https://wa.me/{{ $r->whatsapp_contact }}" target="_blank" class="text-success">
                  <i class="fab fa-whatsapp"></i>{{ $r->whatsapp_contact }}
               </a>
               @else
               N/A
               @endif
            </td>
            <td>{{ $r->fleet_sup }}</td>
            <td>
              {{ $r->stay }}
            </td>

            <td>{{ DB::Table('country')->where('iso' , $r->nationality)->first()->name }}</td>
            <td>
              {{ $r->detail }}
            </td>
            @php
            $users = DB::Table('users')->where('id' , $r->created_by)->first();
            @endphp
            <td>{{ optional($users)->first_name }} {{ optional($users)->last_name }}</td>
            @php
            $users = DB::Table('users')->where('id' , $r->updated_by)->first();
            @endphp
            <td>{{ optional($users)->first_name }} {{ optional($users)->last_name }}</td>
            <td>
               <div class="btn-group">
                     <a  href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#editnote{{ $r->id }}" class='btn  waves-effect'>
                        <i class="fa fa-edit my-1"></i> Edit Note
                     </a>
                  @can('leads_edit')
                     <a  href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#createaccount{{ $r->id }}" class='btn text-primary  waves-effect'>
                        <i class="fa fa-edit my-1"></i> Edit
                     </a>
                  @endcan
                  <form action="{{ route('riderleads.destroy', $r->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn text-danger waves-effect">
                          <i class="fa fa-trash my-1"></i> Delete
                      </button>
                  </form>
               </div>
            </td>
            <td></td>
         </tr>
         <div class="modal modal-default filtetmodal fade" id="createaccount{{ $r->id }}" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
               <div class="modal-content">
                     <div class="modal-header">
                         <h5 class="modal-title">Update Lead</h5>
                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                     </div>
                      <div class="modal-body" id="searchTopbody">
                         <form action="{{ route('riderleads.update' , $r->id) }}" method="POST" id="Leadformupdate">
                             @csrf
                              @method('PUT')
                             <div class="row">
                                 <div class="form-group col-md-12">
                                     <label for="name">Rider Name</label>
                                     <input type="text" name="name" class="form-control" placeholder="Enter Your Account Name" value="{{ $r->name }}">
                                 </div>
                                 <div class="form-group col-md-12">
                                     <label for="contact" style="width: 100%;">Phone Number</label>
                                     <input id="update_contact" type="tel" class="form-control" value="{{ $r->contact }}">
                                       <input type="hidden" name="updatecontact" id="update_contact_full" value="{{ $r->contact }}">
                                 </div>

                                 <div class="form-group col-md-12">
                                     <label for="whatsapp_contact" style="width: 100%;">WhatsApp Number</label>
                                     <input id="update_whatsapp" type="tel" class="form-control" value="{{ $r->whatsapp_contact }}">
                                       <input type="hidden" name="updatewhatsapp_contact" id="update_whatsapp_full" value="{{ $r->whatsapp_contact }}">
                                 </div>
                                 <div class="form-group col-md-12">
                                     <label for="fleet_sup">Fleet SuperVisor</label>
                                     <select class="form-control " id="fleet_sup" name="fleet_sup" required>
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
                                         <option value="" selected>Select</option>
                                         @foreach($fleetSupervisors as $supervisor)
                                             <option value="{{ $supervisor }}" @if($r->fleet_sup == $supervisor) selected @endif>{{ $supervisor }}</option>
                                         @endforeach
                                     </select>
                                 </div>
                                 <div class="form-group col-md-12">
                                     <label for="stay">Stay</label>
                                     <select class="form-control " id="stay" name="stay" required>
                                         <option value="" selected>Select</option>
                                         <option value="In Side" @if($r->stay == 'In Side') selected @endif>In Side</option>
                                         <option value="Out Side" @if($r->stay == 'Out Side') selected @endif>Out Side</option>
                                     </select>
                                 </div>
                                 <div class="form-group col-md-12">
                                     <label for="nationality">Nationality</label>
                                     <select class="form-control " id="nationality" name="nationality" required>
                                         @php
                                         $nationality = DB::table('country')
                                             ->get();
                                         @endphp
                                         <option value="" selected>Select</option>
                                         @foreach($nationality as $att)
                                             <option value="{{ $att->iso }}" @if($r->nationality == $att->iso) selected @endif>{{ $att->name }}</option>
                                         @endforeach
                                     </select>
                                 </div>
                                 <div class="form-group col-md-12">
                                     <label for="admin_charges">Note</label>
                                     <textarea  name="detail" class="form-control" placeholder="Enter Note" required>{{ $r->detail }}</textarea>
                                 </div>
                                 <div class="col-md-12 form-group text-center">
                                     <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Submit</button>
                                 </div>
                             </div>
                         </form>
                      </div>
               </div>
            </div>
         </div>
         <div class="modal modal-default filtetmodal fade" id="editnote{{ $r->id }}" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
               <div class="modal-content">
                     <div class="modal-header">
                         <h5 class="modal-title">Update Lead</h5>
                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                     </div>
                      <div class="modal-body" id="searchTopbody">
                         <form action="{{ route('riderleads.update' , $r->id) }}" method="POST">
                             @csrf
                              @method('PUT')
                             <div class="row">
                                 <div class="form-group col-md-12">
                                     <label for="admin_charges">Note</label>
                                     <textarea  name="detail" class="form-control" placeholder="Enter Note" required>{{ $r->detail }}</textarea>
                                 </div>
                                 <div class="col-md-12 form-group text-center">
                                     <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Submit</button>
                                 </div>
                             </div>
                         </form>
                      </div>
               </div>
            </div>
         </div>
      @endforeach
   </tbody>
</table>
{!! $data->links('pagination') !!}

<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
      <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Riders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
         <div class="modal-body" id="searchTopbody">
            <div style="display: none;" class="loading-overlay" id="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>
            <form id="filterForm" action="{{ route('riders.index') }}" method="GET">
                <div class="row">
                    <div class="form-group col-md-12">
                        <input type="number" name="search" class="form-control" placeholder="Search">
                    </div>
                    <div class="col-md-12 form-group text-center">
                        <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                    </div>
                </div>
            </form>
         </div>
      </div>
   </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>
<script type="text/javascript">
   // For update form
    const updateContact = intlTelInput(document.querySelector("#update_contact"), {
        initialCountry: "ae",
        separateDialCode: true,
        nationalMode: false,
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
    });

    const updateWhatsapp = intlTelInput(document.querySelector("#update_whatsapp"), {
        initialCountry: "ae",
        separateDialCode: true,
        nationalMode: false,
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
    });

    document.querySelector("#Leadformupdate").addEventListener("submit", function () {
        document.querySelector("#update_contact_full").value = updateContact.getNumber();
        document.querySelector("#update_whatsapp_full").value = updateWhatsapp.getNumber();
    });
</script>