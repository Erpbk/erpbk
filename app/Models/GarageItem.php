<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Models\Accounts;
use Illuminate\Support\Str;

class GarageItem extends Model
{
    use LogsActivity;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($garageItem) {
            // Only generate item_code if not already set
            if (empty($garageItem->item_code)) {
                $garageItem->item_code = self::generateUniqueItemCode();
            }
        });
    }

    /**
     * Generate a unique item code
     *
     * @return string
     */
    public static function generateUniqueItemCode()
    {
        $prefix = 'GI-';
        $unique = false;
        $itemCode = '';

        while (!$unique) {
            // Generate a code with format GI-YYYYMMDD-XXXXX where X is random alphanumeric
            $itemCode = $prefix . date('Ymd') . '-' . strtoupper(Str::random(5));

            // Check if this code already exists
            $exists = self::where('item_code', $itemCode)->exists();

            if (!$exists) {
                $unique = true;
            }
        }

        return $itemCode;
    }

    public $table = 'garage_items';

    public $fillable = [
        'name',
        'qty',
        'price',
        'avg_price',
        'total_amount',
        'supplier_id',
        'item_code',
        'status',
        'purchase_date',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'name' => 'string',
        'qty' => 'integer',
        'price' => 'decimal:2',
        'avg_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'item_code' => 'string',
        'status' => 'string',
        'purchase_date' => 'date'
    ];

    public static array $rules = [
        'name' => 'required|string|max:255',
        'qty' => 'required|integer|min:0',
        'price' => 'required|numeric|min:0',
        'supplier_id' => 'required|exists:suppliers,id',
        'item_code' => 'nullable|string|max:50',
        'purchase_date' => 'required|date'
    ];

    public static function dropdown()
    {
        $query = self::select('id', 'name')->pluck('name', 'id')->prepend('Select', '');
        return $query;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    /**
     * Get the supplier account where ref_id equals supplier_id
     */
    public function supplierAccount()
    {
        return Accounts::where('ref_id', $this->supplier_id)
            ->where('ref_name', 'Supplier')
            ->first();
    }

    /**
     * Get the supplier with associated account
     */
    public function getSupplierWithAccount()
    {
        $supplier = $this->supplier;
        $account = $this->supplierAccount();

        return [
            'supplier' => $supplier,
            'account' => $account
        ];
    }

    public function updateStockStatus()
    {
        if ($this->qty == 0) {
            $this->status = 'Out of Stock';
        } elseif ($this->qty < 10) {
            $this->status = 'Low Stock';
        } else {
            $this->status = 'In Stock';
        }

        $this->save();
    }

    /**
     * Find garage items with their supplier and account information
     * 
     * @param int|null $supplierId Filter by supplier ID (optional)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findWithSupplierAndAccount($supplierId = null)
    {
        $query = self::with('supplier');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $items = $query->get();

        // Attach account information to each item
        foreach ($items as $item) {
            $account = Accounts::where('ref_id', $item->supplier_id)
                ->where('ref_name', 'Supplier')
                ->first();

            // Add the account as a dynamic property
            $item->setAttribute('account', $account);
        }

        return $items;
    }
}
