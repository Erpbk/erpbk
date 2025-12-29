<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Helpers\IConstants;

class Supplier extends Model
{
    use LogsActivity, HasActiveStatus, SoftDeletes;

    public $table = 'suppliers';

    public $fillable = [
        'name',
        'email',
        'contact_number',
        'address',
        'tax_number',
        'status',
    ];

    public static $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'contact_number' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:500',
        'tax_number' => 'nullable|string|max:100',
        'status' => 'nullable|boolean',
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relationship: Get the account associated with this supplier
     */
    public function account()
    {
        return $this->hasOne(Accounts::class, 'id', 'account_id');
    }

    /**
     * Relationship: Get all transactions for this supplier
     */
    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'account_id', 'account_id');
    }

    /**
     * Relationship: Get all items associated with this supplier
     */
    public function items()
    {
        return $this->hasMany(Items::class, 'supplier_id', 'id');
    }

    /**
     * Relationship: Get all invoices associated with this supplier
     */
    public function invoices()
    {
        return $this->hasMany(SupplierInvoices::class, 'supplier_id', 'id');
    }

    /**
     * Generate dropdown list of active suppliers
     * Returns an array with id as key and name as value
     * Prepends 'Select' option at the beginning
     */
    public static function dropdown()
    {
        return self::select('id', 'name')
            ->where('status', IConstants::ACTIVE)
            ->pluck('name', 'id')
            ->prepend('Select', '');
    }
}
