<?php
$settings = DB::table('settings')->pluck('value', 'name')->toArray();
?>
<table width="100%" style="font-family: sans-serif;">
    <tr>
        <td width="33.33%"><img src="<?php echo e(URL::asset('assets/img/logo-full.png')); ?>" width="150" /></td>
        <td width="33.33%" style="text-align: center;">
            <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;"><?php echo e($settings['company_name'] ?? ''); ?></h4>
            <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;"><?php echo e($settings['company_address'] ?? ''); ?></p>
            <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;"> TRN <?php echo e($settings['vat_number'] ?? ''); ?></p>
        <td width="33.33%" style="text-align: right;"></td>
    </tr>

    <tr style="text-align: center;">
        <td colspan="3">
            <h4 style="margin-bottom: 15px;margin-top: 25px;font-size: 14px;border-bottom: 1px solid #000;padding: 7px 0px;"> <?php if(isset($voucher_type)): ?><?php echo e($voucher_type); ?><?php endif; ?></h4>
        </td>
    </tr>

</table><?php /**PATH /var/www/laravel/resources/views/_partials/header.blade.php ENDPATH**/ ?>