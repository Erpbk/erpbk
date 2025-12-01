<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Filter Bikes
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="filter-form" action="<?php echo e(route('bikes.filter')); ?>" method="GET">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="bike_code">Bike Code</label>
                        <input type="text" class="form-control" id="bike_code" name="bike_code"
                            value="<?php echo e(request('bike_code')); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="plate">Plate Number</label>
                        <input type="text" class="form-control" id="plate" name="plate"
                            value="<?php echo e(request('plate')); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="rider">Rider</label>
                        <select class="form-control select2" id="rider" name="rider">
                            <option value="">All Riders</option>
                            <?php $__currentLoopData = \App\Models\Riders::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($rider->id); ?>" <?php echo e(request('rider') == $rider->id ? 'selected' : ''); ?>>
                                <?php echo e($rider->name); ?> (<?php echo e($rider->rider_id); ?>)
                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="company">Company</label>
                        <select class="form-control select2" id="company" name="company">
                            <option value="">All Companies</option>
                            <?php $__currentLoopData = \App\Models\LeasingCompanies::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($company->id); ?>" <?php echo e(request('company') == $company->id ? 'selected' : ''); ?>>
                                <?php echo e($company->name); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="emirates">Emirates</label>
                        <select class="form-control" id="emirates" name="emirates">
                            <option value="">All Emirates</option>
                            <?php
                            $emirates = \App\Models\Bikes::distinct()->pluck('emirates')->filter()->sort();
                            ?>
                            <?php $__currentLoopData = $emirates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emirate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($emirate); ?>" <?php echo e(request('emirates') == $emirate ? 'selected' : ''); ?>>
                                <?php echo e($emirate); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="warehouse">Warehouse</label>
                        <select class="form-control" id="warehouse" name="warehouse">
                            <option value="">All Warehouses</option>
                            <?php
                            $warehouses = \App\Models\Bikes::distinct()->pluck('warehouse')->filter()->sort();
                            ?>
                            <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($warehouse); ?>" <?php echo e(request('warehouse') == $warehouse ? 'selected' : ''); ?>>
                                <?php echo e($warehouse); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="1" <?php echo e(request('status') == '1' ? 'selected' : ''); ?>>Active</option>
                            <option value="0" <?php echo e(request('status') == '0' ? 'selected' : ''); ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="quick_search">Quick Search</label>
                        <input type="text" class="form-control" id="quick_search" name="quick_search"
                            value="<?php echo e(request('quick_search')); ?>" placeholder="Search any field...">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <button type="button" id="reset-filters" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/bikes/filter.blade.php ENDPATH**/ ?>