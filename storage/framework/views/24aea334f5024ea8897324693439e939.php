<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('vendor-style'); ?>
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/apex-charts/apex-charts.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/swiper/swiper.css')); ?>" />

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
<!-- Page -->
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/css/pages/cards-advance.css')); ?>">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
<script src="<?php echo e(asset('assets/vendor/libs/apex-charts/apexcharts.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/swiper/swiper.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script src="<?php echo e(asset('assets/js/dashboards-analytics.js')); ?>"></script>
<script>
  window.chartData = {
    pie: {
      labels: <?php echo json_encode($pieData['labels'], 15, 512) ?>,
      values: <?php echo json_encode($pieData['data'], 15, 512) ?>,
      colors: <?php echo json_encode($pieData['colors'], 15, 512) ?>,
    },
    line: {
      labels: <?php echo json_encode($lineData['x'], 15, 512) ?>,
      values: <?php echo json_encode($lineData['y'], 15, 512) ?>,
    }
  };
</script>
<script src="<?php echo e(asset('assets/js/barchat.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>

<?php $__env->startSection('content'); ?>

<div class="row">
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="<?php echo e(route('vendors.index')); ?>">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-user-star ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e(DB::table('vendors')->where('status' , 1)->get()->count()); ?></h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">Active Vendors</p>
          </a>
        </a>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="<?php echo e(route('vendors.index')); ?>">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-user-star ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e(DB::table('vendors')->where('status' , 2)->get()->count()); ?></h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">In Active Vendors</p>
          </a>
        </a>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="<?php echo e(route('riders.index')); ?>">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-user-star ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e(DB::table('riders')->get()->count()); ?></h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">Riders</p>
          </a>
        </a>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="<?php echo e(route('bikes.index')); ?>">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-motorbike ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e(DB::table('bikes')->get()->count()); ?></h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">Bikes</p>
          </a>
        </a>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="<?php echo e(route('sims.index')); ?>">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class=" ti ti-device-sim ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0"><?php echo e(DB::table('sims')->get()->count()); ?></h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">Sims</p>
          </a>
        </a>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <canvas id="myChart" style="width:100%;max-width:600px"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <canvas id="newChart" style="width:100%;max-width:600px"></canvas>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/content/dashboard.blade.php ENDPATH**/ ?>