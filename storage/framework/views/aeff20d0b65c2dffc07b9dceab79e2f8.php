<script src="<?php echo e(asset('js/modal_custom.js')); ?>"></script>

<!-- Frist Name Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('first_name', 'Frist Name:'); ?>

    <?php echo Form::text('first_name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]); ?>

</div>

<!-- Last Name Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('last_name', 'Last Name:'); ?>

    <?php echo Form::text('last_name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]); ?>

</div>



<!-- Phone Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('phone', 'Phone:'); ?>

    <?php echo Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]); ?>

</div>

<!-- Address Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('address', 'Address:'); ?>

    <?php echo Form::text('address', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]); ?>

</div>



<?php if(isset($roles)): ?>
<div class="form-group col-sm-4">

  <?php echo Form::label('roles', 'Role:'); ?>

  <?php echo Form::select('roles', $roles, $userRole??null, ['class' => 'form-control form-select select2 ']); ?>


</div>


<!-- Department Field -->
<div class="form-group col-sm-4">
  <?php echo Form::label('department_id', 'Department:'); ?>

  <?php echo Form::select('department_id', $departments,null ,['class' => 'select2 form-select ','id'=>'department']); ?>

</div>
<!-- Email Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('email', 'Email:'); ?>

    <?php echo Form::email('email', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'maxlength' => 255]); ?>

</div>


<!-- password Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('password', 'Password:'); ?>


<div class="input-group" id="show_hide_password">
    <?php echo Form::password('password', ['class' => 'form-control',  'maxlength' => 255, 'maxlength' => 255]); ?>

    <div class="input-group-text">
        <a href="#" role="button" class="text-dark"><i class="ti ti-eye-off" aria-hidden="true"></i></a>
    </div>
</div>
</div>

<div class="form-group col-sm-4">
    <?php echo Form::label('password_confirmation', 'Confirm Password:'); ?>

    <div class="input-group" id="show_hide_confirm_password">
        <?php echo Form::password('password_confirmation', ['class' => 'form-control',  'maxlength' => 255, 'maxlength' => 255]); ?>

        <div class="input-group-text">
            <a href="#" role="button" class="text-dark"><i class="ti ti-eye-off" aria-hidden="true"></i></a>
        </div>
    </div>
</div>

    <?php if(isset($user)): ?>
    <em>NOTE: If you dont want to change password leave it blank.</em>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Bio Field -->
<div class="form-group col-sm-12">
    <?php echo Form::label('bio', 'Bio:'); ?>

    <?php echo Form::textarea('bio', null, ['class' => 'form-control', 'rows' => 4]); ?>

</div>

<?php if(isset($roles)): ?>
<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
  <div class="form-check">
     <input type="checkbox" name="status" id="status" class="form-check-input" value="1" <?php if(isset($user->status)): ?> checked <?php endif; ?> />
     <label for="status" class="pt-0">Is Active</label>

  </div>
</div>
<?php endif; ?>
<?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/users/fields.blade.php ENDPATH**/ ?>