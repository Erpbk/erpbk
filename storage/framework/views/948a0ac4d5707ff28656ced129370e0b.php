<?php $__env->startSection('page_content'); ?>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Bike" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Bike: activate to sort column ascending">Bike</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Rider</th>
         <th title="Note Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Note Date: activate to sort column ascending">Assign Date</th>
         <th title="Created By" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Created By: activate to sort column ascending">Assign By</th>
         <th title="Note Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Note Date: activate to sort column ascending">Return Date</th>
         <th title="Updated By" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Updated By: activate to sort column ascending">Return By</th>
         <th title="Warehouse" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Warehouse: activate to sort column ascending">Status</th>
         <th title="Notes" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Notes: activate to sort column ascending">Notes</th>
      </tr>
   </thead>
   <tbody>
      <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $bikeHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr class="text-center">
         <td><?php echo e(DB::table('bikes')->where('id' , $r->bike_id)->first()->plate); ?></td>
         <td>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->rider_id): ?>
            <a href="<?php echo e(route('riders.show', $r->rider_id)); ?>" target="_blank"><?php echo e(DB::Table('riders')->where('id' , $r->rider_id)->first()->name); ?></a>
            <?php else: ?>
            -
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
         </td>
         <td>
            <?php
            $contract = DB::table('bike_histories')->where('id', $r->id)->first();
            ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($contract)): ?>
            <a href="<?php echo e(route('bike.contract', $contract->id)); ?>" data-toggle="tooltip" class="file" data-modalID="modal-new" target="_blank"><?php echo e(\Carbon\Carbon::parse($r->note_date)->format('d M Y') ?? '-'); ?></a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contract->contract): ?>
            <a href="<?php echo e(Storage::url('app/contract/'.$contract->contract)); ?>" data-toggle="tooltip" class="file btn btn-success  btn-sm mr-1" data-modalID="modal-new" target="_blank"><?php echo e(\Carbon\Carbon::parse($r->note_date)->format('d M Y') ?? '-'); ?></a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
         </td>
         <td><?php echo e($r->created_by ? \App\Models\User::find($r->created_by)->name : '-'); ?></td>
         <td>
            <?php echo e($r->return_date ? $r->return_date->format('d M Y') : '-'); ?>

         </td>
         <td><?php echo e($r->updated_by ? \App\Models\User::find($r->updated_by)->name : '-'); ?></td>
         <td>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(strtolower(trim($r->warehouse)) === 'active'): ?>
            <span class="badge bg-success"><?php echo e('On Road' ?? '-'); ?></span>
            <?php elseif(strtolower(trim($r->warehouse)) === 'absconded'): ?>
            <span class="badge bg-danger"><?php echo e('On Road' ?? '-'); ?></span>
            <?php elseif(strtolower(trim($r->warehouse)) === 'return'): ?>
            <span class="badge bg-warning"><?php echo e('Off Road' ?? '-'); ?></span>
            <?php elseif(strtolower(trim($r->warehouse)) === 'Vacation'): ?>
            <span class="badge bg-warning"><?php echo e('Off Road' ?? '-'); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
         </td>
         <td>
            <?php echo e($r->notes); ?>

         </td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
   </tbody>
</table>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>
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
<?php echo $__env->make('bikes.edit', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/bike_histories/table.blade.php ENDPATH**/ ?>