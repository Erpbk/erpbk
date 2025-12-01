
<?php if(isset($rider)): ?>
                    <a href="<?php echo e(route('rider.contract', $rider->id)); ?>" data-toggle="tooltip" class="file btn btn-warning  btn-xs mr-1" data-modalID="modal-new" target="_blank"><i class="fas fa-file"></i>&nbsp; View / Print Contract</a>
<?php if($rider->contract): ?>
                        <a href="<?php echo e(Storage::url('app/contract/'.$rider->contract)); ?>" data-toggle="tooltip" class="file btn btn-success  btn-xs mr-1" data-modalID="modal-new" target="_blank"><i class="fas fa-file"></i>&nbsp; Signed Contract</a>
<?php endif; ?>
<?php endif; ?>
                <form action="<?php echo e(url('riders/contract_upload',@$rider->id)); ?>" method="post" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><b>Upload Signed Contract File</b></label>
                                <input name="contract" class="form-control" type="file">
                            </div>
                        </div>
                        <!--col-->
                    </div>
                    <!--row-->
                </div>
                <div class="modal-footer1 mt-3">
                    <button type="submit" class="save_rec btn btn-primary save_rec">Upload</button>
                </div>
            </form>
<?php /**PATH /var/www/laravel/resources/views/riders/contract-modal.blade.php ENDPATH**/ ?>