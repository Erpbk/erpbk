<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class salik extends BaseModel
{
    use HasFactory, LogsActivity, SoftDeletes, BranchScope;
    
    protected $table = 'saliks';
    
    protected $fillable = [
        'branch_id',
        'transaction_id',
        'trip_date',
        'trip_time',
        'billing_month',
        'transaction_post_date',
        'toll_gate',
        'direction',
        'tag_number',
        'plate',
        'amount',
        'salik_vat',
        'salik_vat_amount',
        'trans_date',
        'trans_code',
        'rider_id',
        'rental_company_id',
        'bike_id',
        'admin_charges',
        'admin_vat',
        'admin_vat_amount',
        'vat',
        'salik_account_id',
        'attachments',
        'total_amount',
        'details',
        'status',
        'payment_voucher_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'amount' => 'decimal:2',
        'admin_charges' => 'decimal:2',
        'salik_vat' => 'decimal:2',
        'salik_vat_amount' => 'decimal:2',
        'admin_vat' => 'decimal:2',
        'admin_vat_amount' => 'decimal:2',
        'vat' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $dates = ['deleted_at'];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function bike()
    {
        return $this->belongsTo(Bikes::class, 'bike_id');
    }

    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function rentalCompany()
    {
        return $this->belongsTo(BikeRentCompany::class, 'rental_company_id');
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(Vouchers::class, 'payment_voucher_id');
    }
}
