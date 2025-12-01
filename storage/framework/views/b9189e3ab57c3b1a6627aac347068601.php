<?php $__env->startPush('third_party_stylesheets'); ?>
<link rel="stylesheet" href="<?php echo e(asset('css/garage-items-styles.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<!-- Filter Sidebar -->
<div class="filter-overlay" id="filterOverlay"></div>
<div class="filter-sidebar" id="filterSidebar">
    <div class="filter-header">
        <h5 class="mb-0">Filter Garage Items</h5>
        <button type="button" class="btn-close" id="closeFilterSidebar"></button>
    </div>
    <div class="filter-body">
        <form id="filterForm" class="filter-form">
            <div class="form-group">
                <label for="sidebar_name">Item Name</label>
                <input type="text" name="sidebar_name" id="sidebar_name" class="form-control" placeholder="Search by name" value="<?php echo e(request('name', '')); ?>">
            </div>
            <div class="form-group">
                <label for="sidebar_item_code">Item Code</label>
                <input type="text" name="sidebar_item_code" id="sidebar_item_code" class="form-control" placeholder="Search by item code" value="<?php echo e(request('item_code', '')); ?>">
            </div>
            <div class="form-group">
                <label for="sidebar_supplier_id">Supplier</label>
                <?php echo Form::select('sidebar_supplier_id', $suppliers, request('supplier_id', ''), ['class' => 'form-control', 'id' => 'sidebar_supplier_id']); ?>

            </div>
            <div class="form-group">
                <label for="sidebar_status">Status</label>
                <select name="sidebar_status" id="sidebar_status" class="form-control">
                    <option value="">All Status</option>
                    <option value="In Stock" <?php echo e(request('status') == 'In Stock' ? 'selected' : ''); ?>>In Stock</option>
                    <option value="Low Stock" <?php echo e(request('status') == 'Low Stock' ? 'selected' : ''); ?>>Low Stock</option>
                    <option value="Out of Stock" <?php echo e(request('status') == 'Out of Stock' ? 'selected' : ''); ?>>Out of Stock</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="button" id="sidebar_search" class="btn btn-primary">Apply Filters</button>
                <button type="button" id="sidebar_reset" class="btn btn-secondary">Reset</button>
            </div>
        </form>
    </div>
</div>

<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Garage Items /</span> List
    </h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Garage Items</h5>
            <div class="d-flex gap-2">
                <button type="button" id="openFilterSidebar" class="filter-button">
                    <i class="ti ti-filter"></i>
                    <span>Filter</span>
                </button>
                <a href="<?php echo e(route('garage-items.create')); ?>" class="btn btn-primary float-end">
                    <i class="ti ti-plus me-1"></i>Add New
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php
            $activeFiltersCount = 0;
            if(request('name')) $activeFiltersCount++;
            if(request('item_code')) $activeFiltersCount++;
            if(request('supplier_id')) $activeFiltersCount++;
            if(request('status')) $activeFiltersCount++;
            ?>

            <?php if($activeFiltersCount > 0): ?>
            <div class="filter-status mb-3">
                <div class="filter-info">
                    <i class="ti ti-filter"></i>
                    <span><?php echo e($activeFiltersCount); ?> filter<?php echo e($activeFiltersCount > 1 ? 's' : ''); ?> applied</span>
                    <a href="<?php echo e(route('garage-items.index')); ?>" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="ti ti-x"></i>
                        Clear All
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <div id="table-container">
                <?php echo $__env->make('garage_items.table', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
            <div class="pagination-container">
                <?php if(method_exists($data, 'links')): ?>
                <?php echo e($data->links('components.global-pagination')); ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('page-scripts'); ?>
<script>
    $(document).ready(function() {
        // Filter Sidebar Toggle
        $('#openFilterSidebar').click(function() {
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
            $('body').addClass('overflow-hidden');
        });

        $('#closeFilterSidebar, #filterOverlay').click(function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
            $('body').removeClass('overflow-hidden');
        });

        // Apply filters from sidebar
        $('#sidebar_search').click(function() {
            fetchData();
            closeFilterSidebar();
        });

        // Reset sidebar filters
        $('#sidebar_reset').click(function() {
            $('#sidebar_name').val('');
            $('#sidebar_item_code').val('');
            $('#sidebar_supplier_id').val('');
            $('#sidebar_status').val('');
            fetchData();
            closeFilterSidebar();
        });

        function closeFilterSidebar() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
            $('body').removeClass('overflow-hidden');
        }

        function fetchData(page = 1) {
            $.ajax({
                url: "<?php echo e(route('garage-items.index')); ?>",
                type: "GET",
                data: {
                    name: $('#sidebar_name').val(),
                    item_code: $('#sidebar_item_code').val(),
                    supplier_id: $('#sidebar_supplier_id').val(),
                    status: $('#sidebar_status').val(),
                    page: page
                },
                success: function(response) {
                    $('#table-container').html(response.tableData);
                    $('.pagination-container').html(response.paginationLinks);

                    // Update the filter count
                    let activeFiltersCount = 0;
                    if ($('#sidebar_name').val()) activeFiltersCount++;
                    if ($('#sidebar_item_code').val()) activeFiltersCount++;
                    if ($('#sidebar_supplier_id').val()) activeFiltersCount++;
                    if ($('#sidebar_status').val()) activeFiltersCount++;

                    // Update filter button appearance
                    if (activeFiltersCount > 0) {
                        $('#openFilterSidebar').addClass('active');
                    } else {
                        $('#openFilterSidebar').removeClass('active');
                    }

                    // Update URL with filter parameters without reloading the page
                    let url = new URL(window.location);
                    url.searchParams.set('name', $('#sidebar_name').val() || '');
                    url.searchParams.set('item_code', $('#sidebar_item_code').val() || '');
                    url.searchParams.set('supplier_id', $('#sidebar_supplier_id').val() || '');
                    url.searchParams.set('status', $('#sidebar_status').val() || '');
                    window.history.pushState({}, '', url);

                    // Show filter status if filters are applied
                    if (activeFiltersCount > 0) {
                        let filterStatusHtml = `
                            <div class="filter-status mb-3">
                                <div class="filter-info">
                                    <i class="ti ti-filter"></i>
                                    <span>${activeFiltersCount} filter${activeFiltersCount > 1 ? 's' : ''} applied</span>
                                    <a href="<?php echo e(route('garage-items.index')); ?>" class="btn btn-sm btn-outline-secondary ms-2">
                                        <i class="ti ti-x"></i>
                                        Clear All
                                    </a>
                                </div>
                            </div>
                        `;
                        if ($('.filter-status').length === 0) {
                            $('.card-body').prepend(filterStatusHtml);
                        }
                    } else {
                        $('.filter-status').remove();
                    }
                }
            });
        }

        // Pagination click event
        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            fetchData(page);
        });

        // Initialize filter button state
        let initialActiveFiltersCount = 0;
        if ('<?php echo e(request('
            name ')); ?>') initialActiveFiltersCount++;
        if ('<?php echo e(request('
            item_code ')); ?>') initialActiveFiltersCount++;
        if ('<?php echo e(request('
            supplier_id ')); ?>') initialActiveFiltersCount++;
        if ('<?php echo e(request('
            status ')); ?>') initialActiveFiltersCount++;

        if (initialActiveFiltersCount > 0) {
            $('#openFilterSidebar').addClass('active');
        }
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/garage_items/index.blade.php ENDPATH**/ ?>