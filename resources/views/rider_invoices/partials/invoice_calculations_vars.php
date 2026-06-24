<?php

/** @var \App\Models\RiderInvoices $riderInvoice */

$settings = company_table('settings')->pluck('value', 'name')->toArray();
$total = 0;
$total_qty = 0;
$running_total = 0;
$vat_percentage = Common::getSetting('vat_percentage');
$deliveryfee = company_table('items')->where('name', 'Delivery fees')->first();
$totalOrders = 0;
$billing_month = date('M-y', strtotime($riderInvoice->billing_month));
$riderId = $riderInvoice->rider?->id;

if ($riderId) {
    $fines = company_table('rta_fines')->where('billing_month', $riderInvoice->billing_month)->where('rider_id', $riderId)->sum('total_amount');
    $salik = company_table('saliks')->where('billing_month', $billing_month)->where('rider_id', $riderId)->sum('total_amount');
    $cod = company_table('vouchers')->where('ref_id', $riderId)->where('voucher_type', 'COD')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
    $penalty = company_table('vouchers')->where('ref_id', $riderId)->where('voucher_type', 'PN')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
    $incentive = company_table('vouchers')->where('ref_id', $riderId)->where('voucher_type', 'INC')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
    $advance_salary = company_table('vouchers')->where('ref_id', $riderId)->where('voucher_type', 'AL')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
    $vendor_charges = company_table('vouchers')->where('ref_id', $riderId)->where('voucher_type', 'VC')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
} else {
    $fines = $salik = $cod = $penalty = $incentive = $advance_salary = $vendor_charges = 0;
}

$rider_balance = 0;
if ($riderInvoice->rider && $riderInvoice->rider->account_id) {
    $monthStart = date('Y-m-01', strtotime($riderInvoice->billing_month));
    $rider_balance = \App\Models\Transactions::where('account_id', $riderInvoice->rider->account_id)
        ->whereDate('billing_month', '<', $monthStart)
        ->sum(\DB::raw('debit - credit'));
}
$total_deductions = ($fines > 0 ? $fines : 0) + ($salik > 0 ? $salik : 0) + ($cod > 0 ? $cod : 0) + ($penalty > 0 ? $penalty : 0) + ($advance_salary > 0 ? $advance_salary : 0) + ($vendor_charges > 0 ? $vendor_charges : 0) + ($rider_balance > 0 ? $rider_balance : 0);
$total_additions = ($incentive > 0 ? $incentive : 0) + ($rider_balance < 0 ? abs($rider_balance) : 0);
$totalBeforeTax = 0;
$finalAmount = 0;
$paid_amount = 0;
$rider_balance_final = 0;
$items_total = 0;
$invoiceNumber = \App\Helpers\General::inv_sch($riderInvoice->id, $riderInvoice->created_at);
