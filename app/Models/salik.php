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
        'trans_date',
        'trans_code',
        'rider_id',
        'bike_id',
        'admin_charges',
        'salik_account_id',
        'attachments',
        'total_amount',
        'details',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime'
    ];

    protected $dates = ['deleted_at'];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
