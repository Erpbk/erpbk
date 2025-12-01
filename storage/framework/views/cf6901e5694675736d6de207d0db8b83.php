<?php echo Form::open(['route' => ['users.destroy', $id], 'method' => 'delete','id'=> 'formajax']); ?>

<div class='btn-group' style="float: right;">

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user_view')): ?>
   
    <?php endif; ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user_edit')): ?>
    <a href="javascript:void(0);" data-action="<?php echo e(route('users.edit', $id)); ?>" data-title="Edit User" data-size="xl" class='btn btn-info btn-sm show-modal'>
        <i class="fa fa-edit"></i>
    </a>
    <?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user_delete')): ?>
    <?php if($id !=1 && $id !=2): ?>
    <?php echo Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure, want to delete this user ?")'

    ]); ?>

    <?php endif; ?>
    <?php endif; ?>
</div>
<?php echo Form::close(); ?>

<?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/users/datatables_actions.blade.php ENDPATH**/ ?>