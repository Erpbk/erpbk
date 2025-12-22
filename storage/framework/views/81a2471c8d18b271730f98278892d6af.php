<?php $__env->startPush('third_party_stylesheets'); ?>
<style>
   /* Totals cards */
   .totals-cards {
      display: flex;
      flex-wrap: nowrap;
      gap: 8px;
      margin-bottom: 15px;
   }

   .total-card {
      flex: 1 1 0;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-left-width: 4px;
      border-radius: 8px;
      padding: 8px 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
   }

   .total-card .label {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .3px;
      color: #6b7280;
      margin-bottom: 4px;
   }

   .total-card .label i {
      font-size: 11px;
   }

   .total-card .value {
      font-size: 16px;
      font-weight: 700;
      color: #111827;
   }

   .total-delivered {
      border-left-color: #10b981;
      background: linear-gradient(180deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
   }

   .total-rejected {
      border-left-color: #ef4444;
      background: linear-gradient(180deg, rgba(239, 68, 68, 0.06), rgba(239, 68, 68, 0.02));
   }

   .total-hours {
      border-left-color: #3b82f6;
      background: linear-gradient(180deg, rgba(59, 130, 246, 0.06), rgba(59, 130, 246, 0.02));
   }

   .total-ontime {
      border-left-color: #8b5cf6;
      background: linear-gradient(180deg, rgba(139, 92, 246, 0.06), rgba(139, 92, 246, 0.02));
   }

   .total-valid-days {
      border-left-color: #f59e0b;
      background: linear-gradient(180deg, rgba(245, 158, 11, 0.06), rgba(245, 158, 11, 0.02));
   }

   /* Table header bold and fixed */
   #dataTableBuilder thead th {
      font-weight: bold;
      position: sticky;
      top: 0;
      z-index: 10;
      background-color: #f8f9fa;
      box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
   }

   /* Ensure table container is scrollable */
   .table-responsive {
      max-height: calc(100vh - 300px);
      overflow-y: auto;
      overflow-x: hidden;
      position: relative;
   }

   /* Hide scrollbar for Chrome, Safari and Opera */
   .table-responsive::-webkit-scrollbar {
      display: none;
   }

   /* Hide scrollbar for IE, Edge and Firefox */
   .table-responsive {
      -ms-overflow-style: none;
      /* IE and Edge */
      scrollbar-width: none;
      /* Firefox */
   }
</style>
<?php $__env->stopPush(); ?>


<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Date: activate to sort column ascending">Date</th>
         <th title="Day" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Day: activate to sort column ascending">Day</th>
         <th title="ID" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="ID: activate to sort column ascending">ID</th>
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending">Name</th>
         <th title="Fleet Supr" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Fleet Supr">Fleet Supr</th>
         <th title="Project" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Project">Project</th>
         <th title="Status" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Delivered" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Delivered: activate to sort column ascending">Delivered</th>
         <th title="HR" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="HR: activate to sort column ascending">HR</th>
         <th title="Ontime%" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ontime%: activate to sort column ascending">Ontime%</th>
         <th title="Rejected" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rejected: activate to sort column ascending">Rejected</th>
         <th title="Rating" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rating: activate to sort column ascending">Attendance</th>
      </tr>
   </thead>
   <tbody>
      <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr class="text-center"
         data-delivered="<?php echo e($r->delivered_orders ?? 0); ?>"
         data-rejected="<?php echo e($r->rejected_orders ?? 0); ?>"
         data-hours="<?php echo e($r->login_hr ?? 0); ?>"
         data-ontime="<?php echo e($r->ontime_orders_percentage ?? 0); ?>"
         data-valid="<?php echo e($r->delivery_rating == 'Yes' ? 1 : 0); ?>"
         data-invalid="<?php echo e($r->delivery_rating == 'No' ? 1 : 0); ?>"
         data-off="<?php echo e(($r->delivery_rating != 'Yes' && $r->delivery_rating != 'No') ? 1 : 0); ?>">
         <td><?php echo e(\Carbon\Carbon::parse($r->date)->format('d M Y')); ?></td>
         <td><?php echo e(\Carbon\Carbon::parse($r->date)->format('l')); ?></td>
         <td><?php echo e($r->d_rider_id); ?></td>
         <?php
         $rider = DB::Table('riders')->where('id' , $r->rider_id)->first();
         ?>
         <td> <a href="<?php echo e(route('rider.activities',$r->rider_id)); ?>"><?php echo e($rider->name); ?></a> </td>
         <td><?php echo e($rider->fleet_supervisor); ?></td>
         <td><?php echo e(DB::table('customers')->where('id', $rider->customer_id)->first()->name ?? '-'); ?></td>
         <?php
         $hasActiveBike = DB::table('bikes')->where('rider_id', $rider->id)->where('warehouse', 'Active')->exists();
         $isWalker = $rider->designation === 'Walker';

         if ($isWalker) {
         $statusText = 'Active';
         $badgeClass = 'bg-label-success';
         } else {
         $statusText = $hasActiveBike ? 'Active' : 'Inactive';
         $badgeClass = $hasActiveBike ? 'bg-label-success' : 'bg-label-danger';
         }
         ?>
         <td>
            <span class="badge <?php echo e($badgeClass); ?>"><?php echo e($statusText); ?></span>
         </td>
         <td><?php echo e($r->delivered_orders); ?></td>
         <td><?php echo e($r->login_hr); ?></td>
         <td><?php if($r->ontime_orders_percentage): ?><?php echo e($r->ontime_orders_percentage); ?>% <?php else: ?> - <?php endif; ?></td>
         <td><?php echo e($r->rejected_orders); ?></td>
         <td>
            <?php
            $hours = $r->login_hr ?? 0;

            // Determine status based on new logic
            if ($hours == 0) {
            $status = 'Absent';
            $badgeClass = 'bg-danger';
            } elseif ($hours > 0) {
            $status = 'Present';
            $badgeClass = 'bg-success';
            } else {
            $status = 'Absent';
            $badgeClass = 'bg-warning';
            }
            ?>
            <span class="badge <?php echo e($badgeClass); ?>"><?php echo e($status); ?></span>
         </td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
   </tbody>
</table>
<?php if(method_exists($data, 'links')): ?>
<?php echo $data->links('components.global-pagination'); ?>

<?php endif; ?>


<?php $__env->startPush('scripts'); ?>
<script>
   $(document).ready(function() {
      // Recalculate totals when table is updated (for AJAX filtering)
      calculateTotals();

      // Recalculate when DataTable is redrawn (if using DataTables)
      if ($.fn.DataTable) {
         $('#dataTableBuilder').on('draw.dt', function() {
            calculateTotals();
         });
      }
   });

   function calculateTotals() {
      let workingDays = 0;
      let validDays = 0;
      let invalidDays = 0;
      let offDays = 0;
      let totalOrders = 0;
      let totalRejected = 0;
      let totalHours = 0;
      let totalOntime = 0;
      let ontimeCount = 0;

      $('#dataTableBuilder tbody tr').each(function() {
         const delivered = parseFloat($(this).data('delivered')) || 0;
         const rejected = parseFloat($(this).data('rejected')) || 0;
         const hours = parseFloat($(this).data('hours')) || 0;
         const ontime = parseFloat($(this).data('ontime')) || 0;
         const valid = parseInt($(this).data('valid')) || 0;
         const invalid = parseInt($(this).data('invalid')) || 0;
         const off = parseInt($(this).data('off')) || 0;

         // Count working days (all rows)
         workingDays++;

         // Count day types
         if (valid === 1) {
            validDays++;
         }
         if (invalid === 1) {
            invalidDays++;
         }
         if (off === 1) {
            offDays++;
         }

         // Sum orders and hours
         totalOrders += delivered;
         totalRejected += rejected;
         totalHours += hours;

         // Calculate ontime percentage
         if (ontime > 0) {
            totalOntime += ontime;
            ontimeCount++;
         }
      });

      // Calculate average ontime percentage
      const avgOntime = ontimeCount > 0 ? (totalOntime / ontimeCount) * 100 : 0;

      // Update the totals display
      $('#working_days').text(workingDays);
      $('#valid_days').text(validDays);
      $('#invalid_days').text(invalidDays);
      $('#off_days').text(offDays);
      $('#total_orders').text(totalOrders.toLocaleString());
      $('#avg_ontime').text(avgOntime.toFixed(2) + '%');
      $('#total_rejected').text(totalRejected.toLocaleString());
      $('#total_hours').text(totalHours.toFixed(2));
   }
</script>
<?php $__env->stopPush(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_live_activities/table.blade.php ENDPATH**/ ?>