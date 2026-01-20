<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Models\Vouchers;

class visa_installment_plan extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'visa_installment_plans';

    protected $fillable = [
        'date',
        'billing_month',
        'rider_id',
        'amount',
        'total_amount',
        'reference_number',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';

    // Relationships
    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(Accounts::class, 'rider_id', 'id');
    }

    public function vouchers()
    {
        return $this->hasMany(Vouchers::class, 'ref_id', 'id')
            ->where('voucher_type', 'VL');
    }

    public function getVoucherIdsAttribute()
    {
        if (!$this->relationLoaded('vouchers')) {
            $this->loadMissing('vouchers');
        }

        if ($this->vouchers->isEmpty()) {
            return '';
        }

        return $this->vouchers->map(function ($voucher) {
            $prefix = $voucher->voucher_type ?: 'V';
            $number = str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
            return "{$prefix}-{$number}";
        })->implode(', ');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForRider($query, $riderId)
    {
        return $query->where('rider_id', $riderId);
    }

    // Accessor for status badge
    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            self::STATUS_PAID => '<span class="badge bg-success">Paid</span>',
            self::STATUS_PENDING => '<span class="badge bg-warning">Pending</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }
}
