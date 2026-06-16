<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiderInventoryAssignment extends BaseModel
{
    use HasFactory, SoftDeletes;

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_LOST = 'lost';
    public const STATUS_RETURNED_TO_CUSTOMER = 'returned_to_customer';

    protected $fillable = [
        'rider_id',
        'inventory_item_id',
        'customer_id',
        'assigned_date',
        'assigned_by',
        'status',
        'amount',
        'return_date',
        'returned_by',
        'returned_to_customer',
        'remarks',
        'assignment_contract_number',
        'return_contract_number',
        'trans_code',
        'il_voucher_number',
        'voucher_id',
        'lost_by',
        'loss_date',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'return_date' => 'date',
        'loss_date' => 'date',
        'amount' => 'decimal:2',
        'returned_to_customer' => 'date',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'inventory_item_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function returnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function lostByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lost_by');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Vouchers::class, 'voucher_id');
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }
}
