<?php echo Form::open(['route' => ['dropdowns.destroy', $id], 'method' => 'delete']); ?>

<div class='btn-group'>
    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('dropdown_view')): ?>
    <a href="javascript:void(0);" data-size="lg" data-title="Edit Dropdown" data-action="<?php echo e(route('dropdowns.edit', $id)); ?>" class='show-modal btn btn-info btn-sm'>
        <i class="fa fa-edit"></i>
    </a>
    <?php endif; ?>
   
</div>
<?php echo Form::close(); ?>

<?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/dropdowns/datatables_actions.blade.php ENDPATH**/ ?>