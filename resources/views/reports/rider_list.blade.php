@extends('layouts.app')
@section('content')
<style>
    .table tr:first-child>td {
        position: sticky;
        top: 0;
    }
</style>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item">Reports</li>
                        <li class="breadcrumb-item active">Rider Report</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-12">

                <div class="card rounded-0">
                    <!-- /.card-header -->
                    <div class="card-body">
                        <h3>Rider List</h3>
                        {{-- <form id="form">
                                <div class="row">
                                    <div class="col-md-2">
                                        <input name="df" class="form-control form-control-sm date" placeholder="Date From">
                                    </div>
                                    <div class="col-md-2">
                                        <input name="dt" class="form-control form-control-sm date" placeholder="Date To">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control form-control-sm select2" name="ledger_id">
                                            {!! App\Models\Accounts\TransactionAccount::dropdown() !!}
                                        </select>
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn btn-xs btn-primary" onclick="get_data()"><i class="fa fa-search"></i> </button>
                                    </div>
                                </div>
                                <!--row-->
                            </form> --}}
                        <br>
                        <button class="btn btn-xs btn-primary float-right exportToExcel"><i class="fa fa-file-excel"> Export</i> </button>
                        <table id="table2excel" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Vendor</th>
                                    <th>Status</th>
                                    <th class="d-none">Ethnicity</th>
                                    <th class="d-none">Designation</th>
                                    <th class="d-none">Salary Payout Model</th>
                                    <th class="d-none">Occupation on VISA</th>
                                    <th class="d-none">Project</th>
                                    <th class="d-none">Emirates</th>
                                    <th>Personal No.</th>
                                    <th class="d-none">Company Contact</th>
                                    <th>Bike Number</th>
                                    <th class="d-none">Joining Date</th>
                                    <th class="d-none">DOB</th>
                                    <th class="d-none">Emirates Id (EID)</th>
                                    <th class="d-none">EID Expiry Date</th>
                                    <th class="d-none">License No.</th>
                                    <th class="d-none">License Expiry</th>
                                    <th class="d-none">Nationality</th>
                                    <th class="d-none">Passport No.</th>
                                    <th class="d-none">Passport Expiry Date</th>
                                    <th class="d-none">Passport Handover Status</th>
                                    <th class="d-none">CDM ID</th>
                                    <th class="d-none">Email ID</th>
                                    <th class="d-none">Fleet Supervisor</th>
                                    <th class="d-none">WPS/Non WPS</th>
                                    <th class="d-none">C3 Card</th>

                                </tr>
                            </thead>
                            {{-- <tbody id="get_data"></tbody> --}}
                            @foreach($riders as $row)
                            <tr>
                                <td>{{$row->rider_id}}</td>
                                <td>{{$row->name}}</td>
                                <td>{{@$row->vendor->name}}</td>
                                <td>{{ App\Helpers\CommonHelper::RiderStatus($row->status) }}</td>
                                <td class="d-none">{{$row->ethnicity}}</td>
                                <td class="d-none">{{$row->designation}}</td>
                                <td class="d-none"></td>
                                <td class="d-none">{{$row->visa_occupation}}</td>
                                <td class="d-none">{{@$row->project->name}}</td>
                                <td class="d-none">{{$row->emirate_hub}}</td>
                                <td>{{$row->personal_contact}}</td>
                                <td class="d-none">{{$row->company_contact}}</td>
                                <td>{{@$row->bikes->plate}}</td>
                                <td class="d-none">{{$row->doj}}</td>
                                <td class="d-none">{{$row->dob}}</td>
                                <td class="d-none">{{$row->emirate_id}}</td>
                                <td class="d-none">{{$row->emirate_exp}}</td>
                                <td class="d-none">{{$row->license_no}}</td>
                                <td class="d-none">{{$row->license_expiry}}</td>
                                <td class="d-none">{{@$row->country->name}}</td>
                                <td class="d-none">{{$row->passport}}</td>
                                <td class="d-none">{{$row->passport_expiry}}</td>
                                <td class="d-none">{{$row->passport_handover}}</td>
                                <td class="d-none">{{$row->cdm_deposit_id}}</td>
                                <td class="d-none">{{$row->personal_email}}</td>
                                <td class="d-none">{{$row->fleet_supervisor}}</td>
                                <td class="d-none">{{$row->wps}}</td>
                                <td class="d-none">{{$row->c3_card}}</td>


                            </tr>
                            @endforeach

                        </table>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <div class="pagination-panel"></div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="{{ URL::asset('public/export_excel/jquery.table2excel.js') }}"></script>
<script>
    $(function() {
        //Initialize Select2 Elements
        $('.select2').select2({
            allowClear: true
        });

    });

    function get_data() {
        $("#loader").show();
        $.ajax({
            url: "{{ url('Accounts/reports/get_ledger') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            data: $("#form").serialize(),
            dataType: "JSON",
            success: function(data) {
                $("#get_data").html(data.data);
                $("#loader").hide();
            }
        })
    }
</script>
<script>
    var jq = $.noConflict();
    jq(document).ready(function() {
        $(".exportToExcel").click(function() {
            jq("#table2excel").table2excel({
                filename: "Rider_report.xls",
                exclude: ".noExl",
                name: "Rider Report",
                filename: "Rider_" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
                fileext: ".xls",
                exclude_img: true,
                exclude_links: true,
                exclude_inputs: true,
                preserveColors: true,
            });
        });
    });
</script>
@endsection