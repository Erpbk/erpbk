<?php echo Form::open(['route' => ['files.destroy', $id], 'method' => 'delete','id'=>'formajax']); ?>

<div class='btn-group'>
  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('files_view')): ?>
    <a href="<?php echo e(url('storage2/' . $type . '/'.$type_id.'/'.$file_name)); ?>" target="_blank" class='btn btn-default btn-sm'>
        <i class="fa fa-eye"></i>
    </a>
    <?php endif; ?>
    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('files_delete')): ?>
    <?php echo Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure you want to delete this?")'

    ]); ?>

    <?php endif; ?>
</div>
<?php echo Form::close(); ?>

<?php /**PATH /var/www/laravel/resources/views/files/datatables_actions.blade.php ENDPATH**/ ?>