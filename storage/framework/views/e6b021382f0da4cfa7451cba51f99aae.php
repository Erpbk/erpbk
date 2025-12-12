

<?php $__env->startSection('title','Sims'); ?>
<?php $__env->startSection('content'); ?>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Sims</h3>
                </div>
                <div class="col-sm-6">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sim_create')): ?>
                    <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-size="lg" data-title="New Sim" data-action="<?php echo e(route('sims.create')); ?>">
                        Add New
                    </a>
                    <?php endif; ?>
                    <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
                       <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                          <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Filter Sims</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                             <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="<?php echo e(route('sims.index')); ?>" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="number">Sim Number</label>
                                            <input type="text" name="number" class="form-control" placeholder="Filter By Sim Number" value="<?php echo e(request('number')); ?>">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="emi">EMI Number</label>
                                            <input type="text" name="emi" class="form-control" placeholder="Filter By EMI Number" value="<?php echo e(request('title')); ?>">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="company">Filter by Company</label>
                                            <select class="form-control " id="company" name="company">
                                                <?php
                                                $companies  = DB::table('sims')
                                                    ->whereNotNull('company')
                                                    ->select('company')
                                                    ->distinct()
                                                    ->pluck('company');
                                                ?>
                                                <option value="" selected>Select</option>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($company); ?>" <?php echo e(request('company') == $company ? 'selected' : ''); ?>><?php echo e($company); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="fleet_supervisor">Filter by Fleet Supervisor</label>
                                            <select class="form-control " id="fleet_supervisor" name="fleet_supervisor">
                                                <?php
                                                $fleets  = DB::table('sims')
                                                    ->whereNotNull('fleet_supervisor')
                                                    ->select('fleet_supervisor')
                                                    ->distinct()
                                                    ->pluck('fleet_supervisor');
                                                ?>
                                                <option value="" selected>Select</option>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $fleets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fleet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($fleet); ?>" <?php echo e(request('fleet_supervisor') == $fleet ? 'selected' : ''); ?>><?php echo e($fleet); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
            <div class="card-body table-responsive px-2 py-0"  id="table-data">
                <?php echo $__env->make('sims.table', ['data' => $data], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
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
$(document).ready(function () {
    $('#company').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Company",
            allowClear: true
    });
    $('#fleet_supervisor').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Super Visor",
            allowClear: true
    });
    $('#status').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By status",
            allowClear: true
    });
});
</script>

<script type="text/javascript">
$(document).ready(function () {
    $('#filterForm').on('submit', function (e) {
        e.preventDefault();

        $('#loading-overlay').show();
        $('#searchModal').modal('hide');

        const loaderStartTime = Date.now();

        // Exclude _token and empty fields
        let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
        let formData = $.param(filteredFields);

        $.ajax({
            url: "<?php echo e(route('sims.index')); ?>",
            type: "GET",
            data: formData,
            success: function (data) {
                $('#table-data').html(data.tableData);

                // Update URL
                let newUrl = "<?php echo e(route('sims.index')); ?>" + (formData ? '?' + formData : '');
                history.pushState(null, '', newUrl);

                
                // Ensure loader is visible at least 3s
                const elapsed = Date.now() - loaderStartTime;
                const remaining = 1000 - elapsed;
                setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
            },
            error: function (xhr, status, error) {
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
  document.addEventListener('DOMContentLoaded', function () {
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


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/sims/index.blade.php ENDPATH**/ ?>