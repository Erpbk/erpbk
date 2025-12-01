<?php echo Form::open(['route' => ['riderInvoices.destroy', $id], 'method' => 'delete','id'=>'formajax']); ?>

<div class='btn-group'>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('riderinvoice_view')): ?>
    <a href="<?php echo e(route('riderInvoices.show', $id)); ?>" class='btn btn-default btn-sm' target="_blank">
        <i class="fa fa-eye"></i>
    </a>
    <?php endif; ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('riderinvoice_edit')): ?>
    <a href="javascript:void(0);" data-title="Edit Invoice" data-size="xl" data-action="<?php echo e(route('riderInvoices.edit', $id)); ?>" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    <?php endif; ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('riderinvoice_delete')): ?>
    <?php echo Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-sm',
    'onclick' => 'return confirm("Are you sure?")'

    ]); ?>

    <?php endif; ?>
</div>
<?php echo Form::close(); ?><?php /**PATH /var/www/laravel/resources/views/rider_invoices/datatables_actions.blade.php ENDPATH**/ ?>