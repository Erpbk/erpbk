<?php $__env->startSection('title','Rider Invoices'); ?>
<?php $__env->startSection('content'); ?>
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Rider Invoices</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-success action-btn show-modal mx-2"
                    href="javascript:void(0);" data-size="sm" data-title="Import Rider Invoices" data-action="<?php echo e(route('rider.invoice_import')); ?>">
                    Import Invoices
                </a>

                <a class="btn btn-warning action-btn show-modal mx-2"
                    href="javascript:void(0);" data-size="sm" data-title="Import Paid Invoices" data-action="<?php echo e(route('riderInvoices.importPaid')); ?>">
                    Import Paid Invoices
                </a>

                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-size="xl" data-title="Create Rider Invoice" data-action="<?php echo e(route('riderInvoices.create')); ?>">
                    Create Invoice
                </a>
                <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Filter Rider Invoice</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="<?php echo e(route('riderInvoices.index')); ?>" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="name">ID</label>
                                            <input type="text" name="id" class="form-control" placeholder="Filter By ID" value="<?php echo e(request('id')); ?>">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="rider_id">Filter by Rider</label>
                                            <select class="form-control " id="rider_id" name="rider_id">
                                                <?php
                                                $riderid = DB::table('rider_invoices')
                                                ->whereNotNull('rider_id')
                                                ->where('rider_id', '!=', '')
                                                ->pluck('rider_id')
                                                ->unique();
                                                $riders = DB::table('riders')
                                                ->whereIn('id', $riderid)
                                                ->select('rider_id', 'name', 'id')
                                                ->get();
                                                ?>
                                                <option value="" selected>Select</option>
                                                <?php $__currentLoopData = $riders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($rider->id); ?>" <?php echo e(request('rider_id') == $rider->id ? 'selected' : ''); ?>><?php echo e($rider->rider_id . '-' . $rider->name); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="billing_month">Billing Month</label>
                                            <input type="month" name="billing_month" class="form-control" placeholder="Filter By Billing Month" value="<?php echo e(request('billing_month')); ?>">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="vendor_id">Filter by Vendors</label>
                                            <select class="form-control " id="vendor_id" name="vendor_id">
                                                <?php
                                                $vendorid = DB::table('rider_invoices')
                                                ->whereNotNull('vendor_id')
                                                ->where('vendor_id', '!=', '')
                                                ->pluck('vendor_id')
                                                ->unique();

                                                $vendors = DB::table('vendors')
                                                ->whereIn('id', $vendorid)
                                                ->select('id', 'name')
                                                ->get();
                                                ?>
                                                <option value="" selected>Select</option>
                                                <?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($vendor->id); ?>" <?php echo e(request('vendor_id') == $vendor->id ? 'selected' : ''); ?>><?php echo e($vendor->name); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="zone">Filter by Zone</label>
                                            <select class="form-control " id="zone" name="zone">
                                                <?php
                                                $zones = DB::table('rider_invoices')
                                                ->whereNotNull('zone')
                                                ->where('zone', '!=', '')
                                                ->pluck('zone')
                                                ->unique();
                                                ?>
                                                <option value="" selected>Select</option>
                                                <?php $__currentLoopData = $zones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $zone): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($zone); ?>" <?php echo e(request('zone') == $zone ? 'selected' : ''); ?>><?php echo e($zone); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="performance">Filter by Performance</label>
                                            <select class="form-control " id="performance" name="performance">
                                                <?php
                                                $performances = DB::table('rider_invoices')
                                                ->whereNotNull('performance')
                                                ->where('performance', '!=', '')
                                                ->pluck('performance')
                                                ->unique();
                                                ?>
                                                <option value="" selected>Select</option>
                                                <?php $__currentLoopData = $performances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $performance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($performance); ?>" <?php echo e(request('performance') == $performance ? 'selected' : ''); ?>><?php echo e($performance); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="status">Filter by Status</label>
                                            <select class="form-control " id="status" name="status">
                                                <option value="">Select</option>
                                                <option value="1" <?php echo e(request('status') == 1 ? 'selected' : ''); ?>>Paid</option>
                                                <option value="0" <?php echo e(request('status') == 0 ? 'selected' : ''); ?>>Unpaid</option>
                                            </select>
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
            </div>
        </div>
    </div>
</section>
<div class="content px-3">
    <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            <?php echo $__env->make('rider_invoices.table', [
            'data' => $data,
            'currentMonthTotal' => $currentMonthTotal
            ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .invoice-checkbox {
        transform: scale(1.2);
        cursor: pointer;
    }

    #selectAllCheckbox {
        transform: scale(1.2);
        cursor: pointer;
    }

    #deleteSelectedBtn {
        transition: all 0.3s ease;
    }
</style>
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
        $('#rider_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Rider",
            allowClear: true, // ✅ cross icon enable
        });
        $('#billing_month').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Billing Month",
            allowClear: true, // ✅ cross icon enable
        });
        $('#vendor_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Vendor",
            allowClear: true, // ✅ cross icon enable
        });
        $('#zone').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Zone",
            allowClear: true, // ✅ cross icon enable
        });
        $('#performance').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Performance",
            allowClear: true, // ✅ cross icon enable
        });
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By status",
            allowClear: true, // ✅ cross icon enable
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

            // Exclude _token and empty fields
            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
            let formData = $.param(filteredFields);

            $.ajax({
                url: "<?php echo e(route('riderInvoices.index')); ?>",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);

                    // 🔹 Update Current Month Total in header
                    if (data.currentMonthTotal !== undefined) {
                        $('#current-month-total').text('Current Month Total: ' + data.currentMonthTotal);
                    }

                    // Update URL
                    let newUrl = "<?php echo e(route('riderInvoices.index')); ?>" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);

                    // Loader timing
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

    // Bulk delete functionality
    function toggleSelectAll(selectAllCheckbox) {
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateDeleteButton();
    }

    function updateDeleteButton() {
        const selectedCheckboxes = document.querySelectorAll('.invoice-checkbox:checked');
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');

        if (selectedCheckboxes.length > 0) {
            deleteBtn.style.display = 'inline-block';
            deleteBtn.textContent = `Delete Selected (${selectedCheckboxes.length})`;
        } else {
            deleteBtn.style.display = 'none';
        }

        // Update select all checkbox state
        const allCheckboxes = document.querySelectorAll('.invoice-checkbox');
        if (selectedCheckboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (selectedCheckboxes.length === allCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }

    function deleteSelectedInvoices() {
        const selectedCheckboxes = document.querySelectorAll('.invoice-checkbox:checked');
        const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

        if (selectedIds.length === 0) {
            Swal.fire('Error', 'Please select invoices to delete', 'error');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} invoice(s). This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the selected invoices.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make AJAX request
                $.ajax({
                    url: '<?php echo e(route("riderInvoices.bulkDelete")); ?>',
                    type: 'POST',
                    data: {
                        _token: '<?php echo e(csrf_token()); ?>',
                        invoice_ids: selectedIds
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload the page or refresh the table
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while deleting invoices.';
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
        });
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/rider_invoices/index.blade.php ENDPATH**/ ?>