
<?php $__env->startSection('title','Salik'); ?>
<?php $__env->startSection('content'); ?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3><?php echo e($account->name); ?> | Salik</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-action="<?php echo e(route('salik.create' , $account->id)); ?>" data-size="lg" data-title="New Salik">
                    Add New
                </a>
                <a class="btn btn-success waves-effect waves-light action-btn me-2" href="<?php echo e(route('salik.import.form', $account->id)); ?>">
                    <i class="fas fa-upload"></i> Import Excel
                </a>
                <a class="btn btn-warning waves-effect waves-light action-btn me-2" href="<?php echo e(route('salik.missing.records')); ?>">
                    <i class="fas fa-exclamation-triangle"></i> Missing Records
                </a>
                <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Filter Salik</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="searchTopbody">
                                <form method="GET" action="<?php echo e(route('salik.tickets' , $account->id)); ?>" id="filterForm" class="mb-4">
                                    <div class="row align-items-end">
                                        <div class="col-md-4">
                                            <label for="transaction_id">Transaction ID</label>
                                            <input type="text" name="transaction_id" id="transaction_id" class="form-control" placeholder="Filter By Transaction ID" value="<?php echo e(request('transaction_id')); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="rider_id">Rider</label>
                                            <select name="rider_id" id="rider_id" class="form-contro">
                                                <option value="">All Riders</option>
                                                <?php $__currentLoopData = DB::table('riders')->select('id', 'rider_id', 'name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($rider->id); ?>" <?php echo e(request('rider_id') == $rider->id ? 'selected' : ''); ?>><?php echo e($rider->rider_id); ?> - <?php echo e($rider->name); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="trip_date">Trip Date</label>
                                            <input type="date" name="trip_date" id="trip_date" class="form-control" placeholder="Filter By Trip Date" value="<?php echo e(request('trip_date')); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="billing_month">Billing Month</label>
                                            <input type="month" name="billing_month" id="billing_month" class="form-control" placeholder="Filter By Billing Month" value="<?php echo e(request('billing_month')); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="tag_number">Tag Number</label>
                                            <input type="text" name="tag_number" id="tag_number" class="form-control" placeholder="Filter By Tag Number" value="<?php echo e(request('tag_number')); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="plate">Plate</label>
                                            <input type="text" name="plate" id="plate" class="form-control" placeholder="Filter By Plate" value="<?php echo e(request('plate')); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="direction">Direction</label>
                                            <select name="direction" id="direction" class="form-control">
                                                <option value="">All Directions</option>
                                                <?php $__currentLoopData = DB::table('saliks')->select('direction')->distinct()->whereNotNull('direction')->pluck('direction'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $direction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($direction); ?>" <?php echo e(request('direction') == $direction ? 'selected' : ''); ?>><?php echo e($direction); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="toll_gate">Toll Gate</label>
                                            <select name="toll_gate" id="toll_gate" class="form-control">
                                                <option value="">All Toll Gates</option>
                                                <?php $__currentLoopData = DB::table('saliks')->select('toll_gate')->distinct()->whereNotNull('toll_gate')->pluck('toll_gate'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $toll_gate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($toll_gate); ?>" <?php echo e(request('toll_gate') == $toll_gate ? 'selected' : ''); ?>><?php echo e($toll_gate); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-12 mt-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-end">
                        <div class="w-100">
                            <div class="row gy-3">

                                <div class="col-md-3 col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="badge rounded bg-label-danger me-4 p-2">
                                            <i class="menu-icon tf-icons ti ti-cash"></i>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="mb-0 unpaid-amount"><?php echo e(number_format($unpaidAmount ?? 0, 2)); ?></h5>
                                            <small>Total Unpaid Amount</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="badge rounded bg-label-info me-4 p-2">
                                            <i class="menu-icon tf-icons ti ti-cash"></i>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="mb-0 paid-amount"><?php echo e(number_format($paidAmount ?? 0, 2)); ?></h5>
                                            <small>Total Paid Amount</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="badge rounded bg-label-success me-4 p-2">
                                            <i class="menu-icon tf-icons ti ti-receipt"></i>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="mb-0 paid-count"><?php echo e($paidCount ?? 0); ?></h5>
                                            <small>Paid Salik</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-6">
                                    <div class="d-flex align-items-center">
                                        <div class="badge rounded bg-label-danger me-4 p-2">
                                            <i class="menu-icon tf-icons ti ti-receipt"></i>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="mb-0 unpaid-count"><?php echo e($unpaidCount ?? 0); ?></h5>
                                            <small>Unpaid Salik</small>
                                        </div>
                                    </div>
                                </div>
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
    <div class="card">
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            <?php echo $__env->make('salik.table', ['data' => $data], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
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
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the record.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading()
                    }
                });

                // Make AJAX request to delete
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'The Salik record has been deleted successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload the page to refresh the table
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while deleting the record.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        })
    }
    $(document).ready(function() {
        $('#rider_id').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Rider",
        });
        $('#direction').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Direction",
        });
        $('#toll_gate').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Toll Gate",
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();

            $('#loading-overlay').show();
            $('#searchModal').modal('hide');

            const loaderStartTime = Date.now();

            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
            let formData = $.param(filteredFields);

            $.ajax({
                url: "<?php echo e(route('salik.tickets', $account->id)); ?>",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);

                    // Update the totals
                    $('.paid-amount').text(data.totals.paidAmount);
                    $('.unpaid-amount').text(data.totals.unpaidAmount);
                    $('.paid-count').text(data.totals.paidCount);
                    $('.unpaid-count').text(data.totals.unpaidCount);

                    // Update the URL
                    let newUrl = "<?php echo e(route('salik.tickets', $account->id)); ?>" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);

                    // Minimum 1s loader
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('#dataTableBuilder');
        const headers = table.querySelectorAll('th.sorting');
        const tbody = table.querySelector('tbody');

        headers.forEach((header, colIndex) => {
            header.addEventListener('click', () => {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = header.classList.contains('sorted-asc');

                // Clear previous sort classes
                headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

                // Add new sort direction
                header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

                // Sort logic
                rows.sort((a, b) => {
                    let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
                    let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

                    const aVal = isNaN(aText) ? aText : parseFloat(aText);
                    const bVal = isNaN(bText) ? bText : parseFloat(bText);

                    if (aVal < bVal) return isAsc ? 1 : -1;
                    if (aVal > bVal) return isAsc ? -1 : 1;
                    return 0;
                });

                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/salik/index.blade.php ENDPATH**/ ?>