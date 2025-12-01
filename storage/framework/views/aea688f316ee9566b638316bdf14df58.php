
<!-- Name Field -->
<div class="form-group col-sm-8">
    <?php echo Form::label('name', 'Name:'); ?>

    <?php if(isset($roles)): ?>
    <?php echo Form::text('name', null, ['class' => 'form-control', 'required','readonly', 'maxlength' => 255, 'maxlength' => 255]); ?>


    <?php else: ?>
    <?php echo Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'maxlength' => 255]); ?>

    <?php endif; ?>
</div>
<br>
<h5>Role Permissions</h5>
<div class="table-responsive scrollbar" >
    <table class="table table-flush-spacing">
      <tbody>
       
        <?php
             use Spatie\Permission\Models\Permission;

            $modules = Permission::where(['parent_id'=>0])->orWhere('parent_id',NULL)->get();

        ?>
        <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

        <tr>
          <td class="text-nowrap fw-medium"><?php echo e($module->name); ?></td>
          <?php
              $permissions = Permission::where('parent_id',$module->id)->get();
          ?>
          <td>
            <div class="d-flex">
          <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                <div class="form-check me-3 me-lg-5">
                    <input class="form-check-input" name="permission[]" id="<?php echo e($item->id); ?>" value="<?php echo e($item->name); ?>" type="checkbox"
                    <?php if(isset($rolePermissions[$item->id])): ?> checked <?php endif; ?> >
                    <?php
                         $name = explode('_',$item->name,2);
                        $name = ucwords(str_replace("_"," ",$name[1]));
                    ?>
                    <label class="form-check-label" for="<?php echo e($item->id); ?>"><?php echo e($name); ?></label>
                </div>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
          </td>
        </tr>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      </tbody>
    </table>
  </div>

  <?php $__env->startPush('third_party_scripts'); ?>
     <script>
$("#selectAll").click(function(){

    $(':checkbox').each(function() {
    if(this.checked == true) {
      this.checked = false;
    } else {
      this.checked = true;
    }
  });
})
</script>
  <?php $__env->stopPush(); ?>
<?php /**PATH /var/www/laravel/resources/views/roles/fields.blade.php ENDPATH**/ ?>