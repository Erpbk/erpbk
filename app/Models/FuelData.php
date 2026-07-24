<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class FuelData extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'fuel_data';

    protected $fillable = [
        'inv_id',
        'trans_no',
        'trans_date',
        'billing_month',
        'rider_id',
        'bike_no',
        'card_no',
        'auth_code',
        'site',
        'product',
        'qty',
        'price',
        'subtotal',
        'vat_amount',
        'total',
    ];

    protected $casts = [
        'trans_date' => 'datetime',
        'billing_month' => 'date',
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->inv_id)) {
                $model->inv_id = self::getOrCreateInvId($model->rider_id, $model->billing_month);
            }
        });
    }

    /**
     * Get existing inv_id or create new one for rider/month pair.
     */
    public static function getOrCreateInvId($riderId, $billingMonth)
    {
        // Check if any transaction already exists for this rider and billing month
        Log::info("riderId: $riderId, billingMonth: $billingMonth");
        $existingTransaction = self::where('rider_id', $riderId)
            ->where('billing_month', $billingMonth)
            ->whereNotNull('inv_id')
            ->first();
        
        if ($existingTransaction && $existingTransaction->inv_id) {
            // Return the existing inv_id
            return $existingTransaction->inv_id;
        }
        
        // Generate new inv_id for this rider/month pair
        return self::generateNewInvId($riderId, $billingMonth);
    }

    /**
     * Generate a new unique invoice ID.
     */
    public static function generateNewInvId()
    {
        $result = self::whereNotNull('inv_id')
            ->whereRaw('inv_id REGEXP "^Fuel-[0-9]+$"')
            ->selectRaw('MAX(CAST(REPLACE(inv_id, "Fuel-", "") AS UNSIGNED)) as max_number')
            ->first();

        $maxNumber = $result->max_number ?? 0;
        $newNumber = $maxNumber + 1;
        $sequence = str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        return "Fuel-{$sequence}";
    }

    /**
     * Get all transactions with same invoice ID.
     */
    public function invoiceTransactions()
    {
        return self::where('inv_id', $this->inv_id)->get();
    }

    /**
     * Get invoice summary (all transactions grouped by inv_id).
     */
    public function getMonthlySummary()
    {
        if (!$this->inv_id) {
            return null;
        }
        
        $transactions = self::where('inv_id', $this->inv_id)->get();
        
        return (object)[
            'inv_id' => $this->inv_id,
            'rider_id' => $this->rider_id,
            'rider_name' => $this->rider->name ?? 'N/A',
            'billing_month' => $this->billing_month,
            'transaction_count' => $transactions->count(),
            'total_qty' => $transactions->sum('qty'),
            'total_subtotal' => $transactions->sum('subtotal'),
            'total_vat' => $transactions->sum('vat_amount'),
            'total_amount' => $transactions->sum('total'),
            'service_charges' => (float) $this->service_charges,
            'transactions' => $transactions
        ];
    }

    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id');
    }

    public function card()
    {
        return $this->belongsTo(FuelCards::class, 'card_no', 'card_number');
    }

    public function bike()
    {
        return $this->belongsTo(Bikes::class, 'bike_no', 'plate');
    }

    public function getRiderStatusAttribute()
    {
        $date = $this->trans_date->format('Y-m-d');
        $bikeHistory = BikeHistory::where('rider_id', $this->rider_id)
            ->where('note_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->where('return_date', '>=', $date)
                      ->orWhereNull('return_date');
            })
            ->first();
        if($bikeHistory) {
            return [
                'text' => 'Active',
                'badge' => 'success',
            ];
        }
        return [
            'text' => 'Inactive',
            'badge' => 'danger',
        ];
    }

    public function getServiceChargesAttribute()
    {
        // Service charge is credited to FUEL_ADMIN_CHARGES, not the rider account.
        $fuelIds = self::where('rider_id', $this->rider_id)
            ->whereDate('billing_month', $this->billing_month)
            ->pluck('id');

        if ($fuelIds->isEmpty()) {
            return 0;
        }

        return (float) (Transactions::where('reference_type', 'fuel')
            ->whereIn('reference_id', $fuelIds)
            ->where('narration', 'like', '%service charges%')
            ->where('credit', '>', 0)
            ->value('credit') ?? 0);
    }
}