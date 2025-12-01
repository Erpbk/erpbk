<?php
$configData = Helper::appClasses();
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  <?php if(!isset($navbarFull)): ?>
  <div class="app-brand demo">
    <a href="<?php echo e(route('home')); ?>" class="app-brand-link">
      <span class="app-brand-logo ">
         <img src="<?php echo e(asset('assets/img/logo.png')); ?>" width="50" />
      </span>
      <span class="app-brand-text demo menu-text fw-bold fs-5"><?php echo e(config('variables.templateName')); ?></span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>
  <?php endif; ?>


  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">

    <?php echo $__env->make('layouts.menu', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
  </ul>

</aside><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/layouts/sections/menu/verticalMenu.blade.php ENDPATH**/ ?>