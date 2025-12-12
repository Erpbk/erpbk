<!-- BEGIN: Vendor JS-->
<script src="<?php echo e(asset('assets/vendor/libs/jquery/jquery.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/popper/popper.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/js/bootstrap.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/node-waves/node-waves.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/hammer/hammer.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/typeahead-js/typeahead.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/js/menu.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/toastr/toastr.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/block-ui/block-ui.js')); ?>"></script>
<script src="<?php echo e(asset('assets/vendor/libs/select2/select2.js')); ?>"></script>
<script>
    function formatEmirateId(input) {
        // Save cursor position
        const cursorPosition = input.selectionStart;

        // Remove hyphens and non-digits, then truncate to 15 digits (3+4+7+1)
        let value = input.value.replace(/-/g, '').replace(/\D/g, '').slice(0, 15);

        // Build formatted string with hyphens
        let formatted = '';
        const parts = [3, 4, 7, 1]; // 3 digits, 4 digits, 7 digits, 1 digit
        let currentIndex = 0;

        for (const part of parts) {
            if (value.length > currentIndex) {
                formatted += value.slice(currentIndex, currentIndex + part);
                if (currentIndex + part < 15) formatted += '-'; // Add hyphen only if not the last part
                currentIndex += part;
            }
        }

        // Update input value
        input.value = formatted;

        // Adjust cursor position (account for hyphens)
        const addedHyphens = (formatted.match(/-/g) || []).length;
        const newCursorPosition = Math.min(cursorPosition + addedHyphens, 18); // Cap at maxlength
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    }
</script>
<?php echo $__env->yieldContent('vendor-script'); ?>
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
<script src="<?php echo e(asset('assets/js/main.js')); ?>"></script>
<?php echo $__env->make('layouts.datatables_js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<script src="<?php echo e(asset('js/custom.js')); ?>"></script>
<script src="<?php echo e(asset('js/application.js?id=1')); ?>"></script>


<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
<?php echo $__env->yieldPushContent('pricing-script'); ?>
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
<?php echo $__env->yieldContent('page-script'); ?>
<?php echo $__env->yieldPushContent('page-scripts'); ?>
<?php echo $__env->yieldPushContent('third_party_scripts'); ?>
<script src="<?php echo e(asset('js/bike-import.js')); ?>"></script>
<script src="<?php echo e(asset('js/bike-export.js')); ?>"></script>
<!-- END: Page JS--><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/layouts/sections/scripts.blade.php ENDPATH**/ ?>