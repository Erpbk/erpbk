

<?php $__env->startSection('title','Traffic Fine Details'); ?>
<?php $__env->startSection('content'); ?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Traffic Fine Ticket #<?php echo e($data->ticket_no); ?></h3>
            </div>
            <div class="col-sm-6">
                <div class="modal modal-default filtetmodal fade" id="createaccount" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Account</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="<?php echo e(route('rtaFines.accountcreate')); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label for="name">Name</label>
                                            <input type="text" name="name" class="form-control" placeholder="Enter Your Account Name" required>
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
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-xl-3 col-md-3 col-lg-5 order-1 order-md-0">
            <div class="card mb-6">
                <div class="card-body pt-12">
                    <div class="user-avatar-section">
                        <div class=" d-flex align-items-center flex-column">
                            <div class="user-info text-center">
                                <h6><?php echo e($accounts->name); ?></h6>
                            </div>
                        </div>
                    </div>
                    <h5 class="pb-4 border-bottom mb-4"></h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">
                            <ul class="p-0 mb-3">
                                <li class="list-group-item pb-1">
                                    <b>Account Code:</b> <span class="float-right"><?php echo e($accounts->account_code); ?></span>
                                </li>

                                <li class="list-group-item pb-1">
                                    <b>Account Type:</b> <span class="float-right"><?php echo e($accounts->account_type); ?></span>
                                </li>
                                <li class="list-group-item pb-1">
                                    <b>Status:</b> <span class="float-right">
                                        <?php if($accounts->status == '1'): ?>
                                        <span class="badge  bg-success">Active</span></span>
                                    <?php else: ?>
                                    <span class="badge  bg-success">Active</span></span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-9 col-md-9 col-lg-7 order-0 order-md-1">
            <div class="nav-align-top">
                <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-3 row-gap-2">

                    <li class="nav-item"><a class="nav-link  active  " href="javascript:void(0)"><i class="ti ti-file-upload ti-sm me-1_5"></i>Files</a></li>
                </ul>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <tr>
                                    <th>Ticket Number</th>
                                    <td class="text-end"><?php echo e($data->ticket_no); ?></td>
                                </tr>
                                <tr>
                                    <th>Rider name</th>
                                    <?php
                                    $rider_account = DB::table('riders')->where('id', $data->rider_id)->first();
                                    if ($rider_account) {
                                    $rider = $rider_account;
                                    } else {
                                    $rider = DB::table('accounts')->where('ref_name', 'Rider')->where('id', $data->rider_id)->first();
                                    }
                                    ?>
                                    <td class="text-end"><?php echo e($rider->name ?? '-'); ?></td>
                                </tr>
                                <tr>
                                    <th>Bike Number</th>
                                    <td class="text-end"><?php echo e($data->plate_no); ?></td>
                                </tr>
                                <tr>
                                    <th>Credit Account</th>
                                    <td class="text-end"><?php echo e($accounts->name); ?></td>
                                </tr>
                                <tr>
                                    <th>Transaction Date</th>
                                    <td class="text-end"><?php echo e($data->trip_date); ?></td>
                                </tr>
                                <tr>
                                    <th>Transaction Time</th>
                                    <td class="text-end"><?php echo e($data->trip_time); ?></td>
                                </tr>
                                <tr>
                                    <th>Service Charges</th>
                                    <td class="text-end">AED <?php echo e(number_format($accounts->account_tax , 2)); ?></td>
                                </tr>
                                <tr>
                                    <th>Fine</th>
                                    <td class="text-end">AED <?php echo e(number_format($data->amount , 2)); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Amount</th>
                                    <td class="text-end">AED <?php echo e(number_format($accounts->account_tax + $data->amount, 2)); ?></td>
                                </tr>
                                <tr>
                                    <th>View Files</th>
                                    <?php
                                    $fileUrl = asset('storage/' . $data->attachment_path);
                                    ?>
                                    <td class="text-end"> <a target="_blank" href="<?php echo e($fileUrl); ?>">View File</a> </td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <?php if($data->status == 'paid'): ?>
                                    <td class="text-end"><a href="javascript:void(0);" class="btn btn-action btn-success">Paid</a> </td>
                                    <?php else: ?>
                                    <td class="text-end"><a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#payfine" class="btn btn-action btn-primary">Proceed to Pay Fine</a> </td>
                                    <?php endif; ?>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>]
<div class="modal modal-default filtetmodal fade" id="payfine" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Account to Pay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="searchTopbody">
                <form enctype="multipart/form-data" action="<?php echo e(route('rtaFines.payfine')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo e($data->id); ?>">
                    <input type="hidden" name="rider_id" value="<?php echo e($rider->rider_id); ?>">
                    <input type="hidden" name="trans_date" value="<?php echo e($data->trans_date); ?>">
                    <input type="hidden" name="trans_code" value="<?php echo e($data->trans_code); ?>">
                    <input type="hidden" name="billing_month" value="<?php echo e($data->billing_month); ?>">
                    <input type="hidden" name="payment_type" value="<?php echo e($accounts->account_type); ?>">
                    <input type="hidden" name="voucher_type" value="RFV">
                    <input type="hidden" name="amount" value="<?php echo e($data->total_amount); ?>">
                    <input type="hidden" name="Created_By" value="<?php echo e(Auth::user()->id); ?>">
                    <div class="row">
                        <?php echo $__env->make('rta_fines.voucherfield', ['data' => $data], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        <div class="col-md-12 form-group text-center">
                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script type="text/javascript">
    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        })
    }
    $(document).ready(function() {
        $('#account_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Select Bank Account",
            allowClear: true
        });
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rta_fines/viewvoucher.blade.php ENDPATH**/ ?>