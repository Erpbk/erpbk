
<?php $__env->startSection('title','Rider Leads'); ?>
<?php $__env->startSection('content'); ?>
<style type="text/css">
    .iti{
        width: 100% !important;
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css"/>
<div style="display: none;" class="loading-overlay" id="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>
<section class="content-header">
   <div class="container-fluid">
        <?php
            $dropdown = DB::table('dropdowns')->where('label', 'Fleet Supervisor')->first();
            $fleetSupervisors = $dropdown && $dropdown->values ? json_decode($dropdown->values, true) : [];
        ?>
      <div class="row mb-2">
         <div class="col-sm-6">
            <h3>Leades</h3>
         </div>
         <div class="col-sm-6">
            <div class="d-flex justify-content-end" style="align-items: baseline;">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leads_create')): ?>
                <a class="btn btn-primary action-btn show-modal me-2" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#createaccount">
                Add New
                </a>
                <?php endif; ?>
            </div>
            <div class="modal modal-default filtetmodal fade" id="createaccount" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
               <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                  <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Lead</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                         <div class="modal-body" id="searchTopbody">
                            <form action="<?php echo e(route('riderleads.store')); ?>" method="POST" id="Leadformcreate">
                                <?php echo csrf_field(); ?>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="name">Name</label>
                                        <input type="text" name="name" class="form-control" placeholder="Enter Your Account Name" required>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="contact" style="width: 100%;">Phone Number</label>
                                        <input id="create_contact" type="tel" class="form-control" required>
                                        <input type="hidden" name="contact" id="create_contact_full">
                                    </div>

                                    <div class="form-group col-md-12">
                                        <label for="whatsapp_contact" style="width: 100%;">WhatsApp Number</label>
                                       <input id="create_whatsapp" type="tel" class="form-control" required>
                                        <input type="hidden" name="whatsapp_contact" id="create_whatsapp_full">
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="fleet_sup">Fleet SuperVisor</label>
                                        <select class="form-control " id="fleet_sup" name="fleet_sup" required>
                                            <?php
                                            $supervisorRow = DB::table('dropdowns')
                                                ->where('label', 'Fleet Supervisor')
                                                ->whereNotNull('values')
                                                ->first();
                                            $fleetSupervisors = [];
                                            if ($supervisorRow && $supervisorRow->values) {
                                                $fleetSupervisors = json_decode($supervisorRow->values, true);
                                            }
                                            ?>
                                            <option value="" selected>Select</option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $fleetSupervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($supervisor); ?>"><?php echo e($supervisor); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="stay">Stay</label>
                                        <select class="form-control " id="stay" name="stay" required>
                                            <option value="" selected>Select</option>
                                            <option value="In Side">In Side</option>
                                            <option value="Out Side">Out Side</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="nationality">Nationality</label>
                                        <select class="form-control " id="nationality" name="nationality" required>
                                            <?php
                                            $nationality = DB::table('country')
                                                ->get();
                                            ?>
                                            <option value="" selected>Select</option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $nationality; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($att->iso); ?>"><?php echo e($att->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-12">
                                        <label for="admin_charges">Note</label>
                                        <textarea  name="detail" class="form-control" placeholder="Enter Note" required></textarea>
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
            <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
               <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                  <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Rider Leads</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                     <div class="modal-body" id="searchTopbody">
                        <form id="filterForm" action="<?php echo e(route('riderleads.index')); ?>" method="GET">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="name">Rider Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="Filter By Name" value="<?php echo e(request('name')); ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="fleet_sup">Filter by Fleet SuperVisor</label>
                                    <select class="form-control " id="fleet_sup" name="fleet_sup">
                                        <?php
                                        $supervisorRow = DB::table('dropdowns')
                                            ->where('label', 'Fleet Supervisor')
                                            ->whereNotNull('values')
                                            ->first();
                                        $fleetSupervisors = [];
                                        if ($supervisorRow && $supervisorRow->values) {
                                            $fleetSupervisors = json_decode($supervisorRow->values, true);
                                        }
                                        ?>
                                        <option value="" selected>Select</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $fleetSupervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($supervisor); ?>" <?php echo e(request('fleet_supervisor') == $supervisor ? 'selected' : ''); ?>><?php echo e($supervisor); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="nationality">Filter by Nationality</label>
                                    <select class="form-control " id="nationality" name="nationality">
                                        <?php
                                        $nationality = DB::table('rider_hirings')
                                            ->whereNotNull('nationality')
                                            ->where('nationality', '!=', '')
                                            ->select('nationality')
                                            ->distinct()
                                            ->pluck('nationality');
                                        ?>
                                        <option value="" selected>Select</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $nationality; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($att); ?>" <?php echo e(request('nationality') == $att ? 'selected' : ''); ?>><?php echo e(DB::table('country')->where('iso' , $att)->first()->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="status">Filter by Stay Status</label>
                                    <select class="form-control" id="stay" name="stay">
                                        <option value="" selected>Select</option>
                                        <option value="In Side"  <?php echo e(request('stay') == 'In Side' ? 'selected' : ''); ?>>In Side</option>
                                        <option value="Out Side" <?php echo e(request('stay') == 'Out Side' ? 'selected' : ''); ?>>Out Side</option>
                                    </select>
                                </div>
                                <div class="col-md-12 form-group text-center">
                                    <button type="submit" name="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
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
<div class="content px-0">
    <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="card">
        <div class="card-body table-responsive px-2 py-0"  id="table-data">
            <?php echo $__env->make('riders.hiring_table', ['data' => $data], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>

<!-- JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>
<script type="text/javascript">
$(document).ready(function () {
    $('#fleet_sup').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Fleet SuperVisor",
            allowClear: true
    });
    $('#stay').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Stay",
            allowClear: true
    });
    $('#status').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By status",
            allowClear: true
    });
    $('#nationality').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Nationality",
            allowClear: true
    });
    $('#country_code').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Country",
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
        let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
        let formData = $.param(filteredFields);
        $.ajax({
            url: "<?php echo e(route('riderleads.index')); ?>",
            type: "GET",
            data: formData,
            success: function (data) {
                $('#table-data').html(data.tableData);
                let newUrl = "<?php echo e(route('riderleads.index')); ?>" + (formData ? '?' + formData : '');
                history.pushState(null, '', newUrl);
                if (filteredFields.length > 0) {
                    $('#clearFilterBtn').show();
                } else {
                    $('#clearFilterBtn').hide();
                }
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

<script>
    // For create form
    const createContact = intlTelInput(document.querySelector("#create_contact"), {
        initialCountry: "ae",
        separateDialCode: true,
        nationalMode: false,
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
    });

    const createWhatsapp = intlTelInput(document.querySelector("#create_whatsapp"), {
        initialCountry: "ae",
        separateDialCode: true,
        nationalMode: false,
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
    });

    document.querySelector("#Leadformcreate").addEventListener("submit", function () {
        document.querySelector("#create_contact_full").value = createContact.getNumber();
        document.querySelector("#create_whatsapp_full").value = createWhatsapp.getNumber();
    });
</script>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/riders/hiring_index.blade.php ENDPATH**/ ?>