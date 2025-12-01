<!-- Type Field -->
<input type="hidden" name="type" value="<?php echo e(request('type')); ?>"/>
<input type="hidden" name="type_id" value="<?php echo e(request('type_id')); ?>"/>



<!-- File Name Field -->
<div class="col-12">
  <label class=" pl-2">Name</label>
  <input type="text" name="name" class="form-control mb-3" style="height: 40px;" />

</div>

<div class="col-12">
  <label class=" pl-2">Select file</label>
  <input type="file" name="file_name" class="form-control mb-3" style="height: 40px;" />

</div>
<!-- Expiry Date Field -->


<?php /**PATH /var/www/laravel/resources/views/files/fields.blade.php ENDPATH**/ ?>