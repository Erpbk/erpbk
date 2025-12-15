<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
<style>
   td:focus,
   th:focus {
      outline: 2px solid #2196f3;
      outline-offset: -2px;
      background: #e3f2fd;
   }

   th {
      white-space: nowrap;
   }
</style>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="">
      <tr role="row">
         <?php
         $tableCols = $tableColumns ?? [];
         $dataColumns = array_values(array_filter($tableCols, function($c){
         $k = $c['data'] ?? ($c['key'] ?? null);
         return $k !== 'search' && $k !== 'control';
         }));
         ?>
         <?php $__currentLoopData = $dataColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
         <?php $title = $col['title'] ?? ($col['name'] ?? ($col['data'] ?? '')); ?>
         <th title="<?php echo e($title); ?>" class="sorting" tabindex="0" rowspan="1" colspan="1"><?php echo e($title); ?></th>
         <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a class="openFilterSidebar" href="javascript:void(0);"> <i class="fa fa-search"></i></a>
         </th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a class="openColumnControlSidebar" href="javascript:void(0);" title="Column Control"> <i class="fa fa-columns"></i></a>
         </th>
      </tr>
   </thead>
   <tbody>
      <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr class="text-center">
         <?php $__currentLoopData = $dataColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
         <?php $key = $col['data'] ?? ($col['key'] ?? null); ?>
         <?php switch($key):
         case ('bike_code'): ?>
         <td tabindex="0"><?php echo e($r->bike_code); ?></td>
         <?php break; ?>
         <?php case ('plate'): ?>
         <td tabindex="0" class="text-start"><a href="<?php echo e(route('bikes.show', $r->id)); ?>"><?php echo e($r->plate); ?></a></td>
         <?php break; ?>
         <?php case ('rider_id'): ?>
         <?php
         $rider = DB::table('riders')->where('id', $r->rider_id)->first();
         ?>
         <td tabindex="0"><?php echo e($rider->rider_id ?? '-'); ?></td>
         <?php break; ?>
         <?php case ('rider_name'): ?>
         <?php
         $rider = DB::table('riders')->where('id', $r->rider_id)->first();
         ?>
         <td tabindex="0">
            <?php if($rider): ?>
            <a href="<?php echo e(route('riders.show', $rider->id)); ?>"><?php echo e($rider->name); ?></a>
            <?php else: ?>
            -
            <?php endif; ?>
         </td>
         <?php break; ?>
         <?php case ('emirates'): ?>
         <td tabindex="0"><?php echo e($r->emirates); ?></td>
         <?php break; ?>
         <?php case ('company'): ?>
         <?php
         $company = DB::Table('leasing_companies')->where('id' , $r->company)->first();
         ?>
         <td tabindex="0"><?php echo e($company ? $company->name : '-'); ?></td>
         <?php break; ?>
         <?php case ('customer_id'): ?>
         <td tabindex="0"><?php echo e(DB::table('customers')->where('id' , $r->customer_id)->first()->name ?? '-'); ?></td>
         <?php break; ?>
         <?php case ('expiry_date'): ?>
         <td tabindex="0"><?php echo e($r->expiry_date ? \Carbon\Carbon::parse($r->expiry_date)->format('d M Y') : '-'); ?></td>
         <?php break; ?>
         <?php case ('warehouse'): ?>
         <td tabindex="0">
            <?php
            $bike_warehouse = DB::table('bike_histories')->where('bike_id', $r->id)->first();
            $badgeClass = match($bike_warehouse->warehouse ?? 'Inactive') {
            'Active' => 'bg-label-success',
            'Return' => 'bg-label-warning',
            'Vacation' => 'bg-label-info',
            'Absconded' => 'bg-label-danger',
            'Inactive' => 'bg-label-danger',
            };
            ?>
            <span class="badge <?php echo e($badgeClass); ?>"><?php echo e($bike_warehouse->warehouse ?? 'Inactive'); ?></span>
         </td>
         <?php break; ?>
         <?php case ('status'): ?>
         <td tabindex="0">
            <?php
            $statusText = $r->status == 1 ? 'Active' : 'Inactive';
            $badgeClass = $r->status == 1 ? 'bg-label-success' : 'bg-label-danger';
            ?>
            <span class="badge <?php echo e($badgeClass); ?>"><?php echo e($statusText); ?></span>
         </td>
         <?php break; ?>
         <?php case ('created_by'): ?>
         <td tabindex="0"><?php echo e($r->created_by ? \App\Models\User::find($r->created_by)->name : '-'); ?></td>
         <?php break; ?>
         <?php case ('updated_by'): ?>
         <td tabindex="0"><?php echo e($r->updated_by ? \App\Models\User::find($r->updated_by)->name : '-'); ?></td>
         <?php break; ?>
         <?php case ('action'): ?>
         <td tabindex="0" style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_<?php echo e($r->id); ?>" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_<?php echo e($r->id); ?>" style="z-index: 1050;">
                  <a href="<?php echo e(route('bikes.show', $r->id)); ?>" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye my-1"></i>Show Bike
                  </a>
                  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('item_edit')): ?>
                  <a href="<?php echo e(route('bikes.edit', $r->id)); ?>" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit my-1"></i>Edit
                  </a>
                  <?php endif; ?>
                  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('item_delete')): ?>
                  <a href="javascript:void(0);" data-url="<?php echo e(route('bikes.delete', $r->id)); ?>" class='dropdown-item waves-effect delete-bike'>
                     <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  <?php endif; ?>
               </div>
            </div>
         </td>
         <?php break; ?>
         <?php default: ?>
         <td tabindex="0"><?php echo e(data_get($r, $key, '-')); ?></td>
         <?php endswitch; ?>
         <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
         <td></td>
         <td></td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
   </tbody>
</table>
<?php if(method_exists($data, 'links')): ?>
<?php echo $data->links('components.global-pagination'); ?>

<?php endif; ?>

<!-- Filter modal removed: using right-side sliding sidebar instead --><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/bikes/table.blade.php ENDPATH**/ ?>