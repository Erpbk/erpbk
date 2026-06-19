<?php

namespace App\Models;

use App\Traits\BranchScope;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transactions extends BaseModel
{
    use BranchScope, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'trans_code',
        'trans_date',
        'reference_id',
        'reference_type',
        'account_id',
        'debit',
        'credit',
        'billing_month',
        'narration',
        'deleted_by',
    ];

    public static $rules = [
        'branch_id' => 'nullable|exists:branches,id',
    ];

    public function account()
    {
        return $this->hasOne(Accounts::class, 'id', 'account_id');
    }

    public function voucher()
    {
        return $this->hasOne(Vouchers::class, 'trans_code', 'trans_code');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function supplierInvoice()
    {
        return $this->hasOne(SupplierInvoices::class, 'voucher_id');
    }

    public function getVoucherNumberAttribute()
    {
        $voucher_text = 'Not Found';

        if ($this->voucher) {
            $voucher_text = $this->voucher->voucher_type.'-'.str_pad($this->voucher->id, 4, '0', STR_PAD_LEFT);
        } elseif ($this->reference_type == 'Invoice') {
            $voucher_text = 'RD-'.str_pad($this->reference_id, 4, '0', STR_PAD_LEFT);
        } elseif ($this->reference_type == 'LeasingCompanyInvoice') {
            $invoice_ID = $this->reference_id;
            $voucher_text = 'LI-'.str_pad($invoice_ID, 4, '0', STR_PAD_LEFT);
        } elseif ($this->reference_type == 'Bike Maintenance') {
            $voucher_text = 'MA-'.str_pad($this->reference_id, 4, '0', STR_PAD_LEFT);
        } elseif ($this->reference_type == 'LeasingCompanyBillingInvoice' || $this->reference_type == 'Rental Invoice') {
            $invoice_ID = $this->reference_id;
            $voucher_text = 'RBI-'.str_pad($invoice_ID, 4, '0', STR_PAD_LEFT);
        } elseif ($this->reference_type == 'CI') {
            $voucher_text = 'CI-'.str_pad($this->reference_id, 4, '0', STR_PAD_LEFT);
        } elseif ($this->reference_type == 'EmployeeInvoice') {
            $voucher_text = 'EMP-'.str_pad($this->reference_id, 4, '0', STR_PAD_LEFT);
        } elseif ($this->reference_type == 'fuel') {
            $invoice = FuelData::find($this->reference_id);
            if ($invoice) {
                $voucher_text = $invoice->inv_id;
            } else {
                $voucher_text = 'Not Found';
            }
        } elseif ($this->reference_type == 'SimInvoice') {
            $invoice = SimInvoice::find($this->reference_id);
            if ($invoice) {
                $voucher_text = $invoice->invoice_number;
            } else {
                $voucher_text = 'SIM-'.str_pad($this->reference_id, 4, '0', STR_PAD_LEFT);
            }
        } elseif ($this->reference_type == 'SUP') {
            $invoice = SupplierInvoices::find($this->reference_id);
            if ($invoice) {
                $voucher_text = $invoice->invoice_number;
            } else {
                $voucher_text = 'SUP-'.str_pad($this->reference_id, 4, '0', STR_PAD_LEFT);
            }
        } elseif (in_array($this->reference_type, ['FAV', 'FDV'], true)) {
            $voucher = $this->voucher ?? Vouchers::where('trans_code', $this->trans_code)->first();
            if ($voucher) {
                $voucher_text = $voucher->voucher_type.'-'.str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
            }
        }

        return $voucher_text;
    }
}
