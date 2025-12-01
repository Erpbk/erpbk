
                <form action="{{url('riders/job_status',@$rider->id)}}" method="post" enctype="multipart/form-data" id="formajax">
@csrf
{{-- <input type="hidden" id="reload_page" value="1" /> --}}
<input type="hidden" name="job_status" value="1" />
                    <div class="row mt-1">
                        <div class="col-md-12">
                            {{-- <div class="form-group">
                                <label>Job Status <span style="color:red;">*</span></label>
                                        <select class="form-select" name="job_status">
                                            <option value="">Select</option>
                                            @foreach(App\Helpers\General::JobStatus() as $key=>$value)
                                            <option value="{{$key}}"@if(@$result['status']==$key)selected @endif>{{$value}}</option>
                                            @endforeach
                                        </select>
                            </div> --}}
                            <div class="form-group">
                                <label>Reason</label>
                                <textarea rows="5" name="reason" class="form-control"></textarea>
                            </div>
                        </div>
                        <!--col-->
                    </div>
                    <!--row-->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="save_rec btn btn-primary save_rec">Save</button>
                </div>
            </form>
