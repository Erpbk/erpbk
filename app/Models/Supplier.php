<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;
use App\Helpers\IConstants;

class Supplier extends BaseModel
{
    use LogsActivity, HasActiveStatus, SoftDeletes, BranchScope;

    public $table = 'suppliers';

    public $fillable = [
        'branch_id',
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
     * Suppliers with ledger activity must never be deleted (deactivate instead).
     */
    public function cannotBeDeletedReason(): ?string
    {
        if ($this->account_id) {
            $transactionCount = Transactions::withTrashed()
                ->withoutGlobalScope('branch')
                ->where('account_id', $this->account_id)
                ->count();

            if ($transactionCount > 0) {
                return 'Cannot delete supplier. This supplier\'s account has '
                    . $transactionCount
                    . ' transaction(s). Deactivate the supplier instead.';
            }
        }

        $invoiceCount = $this->invoices()->where('is_invoice', true)->count();
        if ($invoiceCount > 0) {
            return 'Cannot delete supplier. This supplier has '
                . $invoiceCount
                . ' invoice(s). Delete or recycle those invoices first.';
        }

        return null;
    }

    public function purchaseOrders()
    {
        return $this->invoices()->where(function ($query) {
            $query->where('is_invoice', false)->orWhereNull('is_invoice');
        });
    }

    /**
     * Generate dropdown list of active suppliers
     * Returns an array with id as key and name as value
     * Prepends 'Select' option at the beginning
     */
    public static function dropdown()
    {
        return self::select('id', 'name')
            ->where('status', 1)
            ->pluck('name', 'id')
            ->prepend('Select', '');
    }
}
