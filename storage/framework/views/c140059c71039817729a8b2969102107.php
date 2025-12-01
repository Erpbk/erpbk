
<?php echo Form::model($vouchers, ['route' => ['vouchers.update', $vouchers->id], 'method' => 'patch', 'id'=>'formajax']); ?>


<div class=" p-3">
    <?php echo $__env->make('vouchers.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>


<div class="card-footer">
<?php echo Form::submit('Save', ['class' => 'btn btn-primary','onclick'=>'getTotal();']); ?>

</div>

<?php echo Form::close(); ?>




<script>
$(document).on('ready',function(){
    $(".append-line").empty();
});

</script>

<?php /**PATH /var/www/laravel/resources/views/vouchers/edit.blade.php ENDPATH**/ ?>