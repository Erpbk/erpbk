<!doctype html>
<html style="height: 100%;box-sizing: border-box;">

<head>
    <?php

    $voucher_type = App\Helpers\General::VoucherType($voucher->voucher_type);
    $i=0;
    ?>
    <meta charset="utf-8">
    <title><?php echo e($voucher_type); ?> # <?php echo e($voucher->voucher_type.'-'.str_pad($voucher->id,4,"0",STR_PAD_LEFT)); ?></title>
    <style>
        .page-footer,
        .page-footer-space {
            /*height: 39px;*/
        }

        .page-footer {
            position: relative;
            bottom: 0;
            width: 100%;
            left: 0;
        }

        .headerDiv {
            position: relative;
            width: 33.33%;
            float: left;
            min-height: 1px;
        }

        #btns {
            position: relative;
            bottom: 20px;
        }

        /*.footer{
            position: absolute;bottom: 0;height: 39px;
        }*/
        .pcontainer {
            position: relative;
            height: 100%;
        }

        hr {
            margin-bottom: 2px;
            margin-top: 2px;
        }

        @media print {
            #btns {
                display: none;
            }

            @page {
                margin: 0 0.10cm;
                margin-top: 10px;
            }

            html,
            body {
                padding: 20px;
                margin: 0;
            }

            #pnumber:after {
                counter-increment: page;
                content: "Page " counter(page);
            }

            .page-footer {
                position: absolute;
            }

        }

        @media print {
            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                font-size: 12px;
                border-top: 1px solid #000;
                padding-top: 5px;
                display: flex;
                justify-content: space-between;
            }
        }
    </style>
</head>


<body style="">
    <div style="position: relative;min-height: 100%;height: 100%;">
        <?php echo $__env->make('_partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php if(isset($voucher)): ?>
        <table width="100%" style="font-family: sans-serif; margin-top: 20px;font-size: 12px">
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;"><strong>Voucher No</strong>: <?php echo e($voucher->voucher_type . '-' . str_pad($voucher->id, '4', '0', STR_PAD_LEFT)); ?></td>
                <th style="padding: 3px;width: 15%;text-align: left;">Voucher Date:</th>
                <td style="padding: 3px;width: 20%;text-align: left;"><?php echo e($voucher->trans_date); ?></td>
            </tr>
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;"><strong>Voucher Type</strong>:<?php echo e($voucher_type); ?></td>
                <?php if(isset($voucher->billing_month)): ?>
                <th style="padding: 3px;width: 15%;text-align: left;"> Billing Month: </th>
                <td style="padding: 3px;width: 20%;text-align: left;"><?php echo e(date('M-Y',strtotime($voucher->billing_month))); ?></td>
                <?php endif; ?>
            </tr>
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;"><strong>Created By</strong>: <?php echo e(Auth::user()->where('id', $voucher->Created_By)->first()->name ?? 'N/A'); ?></td>
                <th style="padding: 3px;width: 15%;text-align: left;">Creation Date:</th>
                <td style="padding: 3px;width: 20%;text-align: left;"><?php echo e(Illuminate\Support\Carbon::parse($voucher->created_at)->format('d-M-Y ')); ?></td>
            </tr>
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;">&nbsp;</td>
            </tr>
        </table>
        <table style="width: 100%; font-family: sans-serif;text-align: left;border: 1px solid #000; border-collapse: collapse; margin-top: 20px;font-size: 12px;">
            <thead>
                <tr style="border: 1px solid #000;">
                    <th style="border: 1px solid #000; padding: 10px;width: 20px;">Sr</th>
                    <th style="border: 1px solid #000; padding: 10px;">Account Name</th>
                    <th style="border: 1px solid #000; padding: 10px;">Particulars</th>
                    <th style="border: 1px solid #000; padding: 10px;text-align: center;">Debit</th>
                    <th style="border: 1px solid #000; padding: 10px;text-align: center;">Credit</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalD = 0;
                $totalC = 0;
                $fin_detail = DB::Table('rta_fines')->where('id' , $voucher->ref_id)->first();
                ?>
                <?php $__currentLoopData = $voucher->transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td style="padding: 5px;border:1px solid;text-align:center;"><?php echo e($i+=1); ?></td>
                    <td style="padding: 5px;border:1px solid">
                        <?php echo e(@$item->account->account_code); ?>-<?php echo e(@$item->account->name); ?>

                    </td>
                    <?php if($voucher->voucher_type == 'RFV'): ?>
                    <td style="padding: 5px;border:1px solid;text-align: left"><?php echo e($item->narration); ?>

                        <b>Ticket No:</b><?php echo e($fin_detail->ticket_no ?? ''); ?>,<b>Bike No:</b><?php echo e($fin_detail->plate_no ?? ''); ?>, <?php if($fin_detail && $fin_detail->trip_date): ?> <?php echo e(\Carbon\Carbon::parse($fin_detail->trip_date)->format('d M Y')); ?> <?php else: ?> N/A <?php endif; ?>
                    </td>
                    <?php else: ?>
                    <td style="padding: 5px;border:1px solid;text-align: left"><?php echo e($item->narration); ?></td>
                    <?php endif; ?>
                    <td style="padding:5px;border:1px solid;text-align: center;"><?php echo e($item->debit); ?></td>
                    <td style="padding:5px;border:1px solid;text-align: center;"><?php echo e($item->credit); ?></td>
                </tr>
                <?php
                $totalD+=$item->debit;
                $totalC+=$item->credit;
                ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 1px solid #000;">
                    <td colspan="2" style="padding: 10px;text-align: center;"></td>
                    <th style="padding: 10px;text-align: right;">Sub Total:</th>
                    <th style="padding: 10px;text-align: center;"><?php echo e(\App\Helpers\Account::show_bal_format($totalD)); ?></th>
                    <th style="padding: 10px;text-align: center;"><?php echo e(\App\Helpers\Account::show_bal_format($totalC)); ?></th>
                </tr>
                <tr style="border-top: 1px solid #000; background-color: #dfdfdf;">
                    <td colspan="2" style="padding: 10px;text-align: center;"></td>
                    <th style="padding: 10px;text-align: right;">Total:</th>
                    <th style="padding: 10px;text-align: center;">AED<?php echo e(\App\Helpers\Account::show_bal_format($totalD)); ?></th>
                    <th style="padding: 10px;text-align: center;">AED<?php echo e(\App\Helpers\Account::show_bal_format($totalC)); ?></th>
                </tr>
            </tfoot>
        </table>
        <div id="btns" style="margin-top: 50px">
            <button class="btn btn-sm btn-outline-danger" type="button" onClick="window.print()"><i class="fa fa-file-pdf-o"></i> Print</button>
        </div>
        <?php else: ?>
        <div class="text-danger">No Voucher found</div>
        <?php endif; ?>
        <div class="print-footer">
            <span class="left">Printed date: <?php echo e(now()->format('d-M-Y')); ?></span>
            <span class="right">Printed by: <?php echo e(auth()->user()->name ?? 'System'); ?></span>
        </div>


    </div>
</body>

</html><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/vouchers/show_fields.blade.php ENDPATH**/ ?>