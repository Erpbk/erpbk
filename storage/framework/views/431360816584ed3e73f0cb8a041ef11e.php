<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/fonts/tabler-icons.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/fonts/fontawesome.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/fonts/flag-icons.css')); ?>" />
<!-- Core CSS -->
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/css' .$configData['rtlSupport'] .'/core.css')); ?>" class="<?php echo e($configData['hasCustomizer'] ? 'template-customizer-core-css' : ''); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/css' .$configData['rtlSupport'] .'/' .$configData['theme'].'.css')); ?>" class="<?php echo e($configData['hasCustomizer'] ? 'template-customizer-theme-css' : ''); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/css/demo.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/node-waves/node-waves.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/typeahead-js/typeahead.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/toastr/toastr.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/spinkit/spinkit.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('assets/vendor/libs/select2/select2.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('css/custom.css')); ?>" />
<link rel="stylesheet" href="<?php echo e(asset('css/fixed-footer.css')); ?>?v=1.3" />

<!-- Vendor Styles -->
<?php echo $__env->yieldContent('vendor-style'); ?>


<!-- Page Styles -->
<?php echo $__env->yieldContent('page-style'); ?>
<?php echo $__env->yieldPushContent('page-styles'); ?>
<?php echo $__env->yieldPushContent('third_party_stylesheets'); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/layouts/sections/styles.blade.php ENDPATH**/ ?>