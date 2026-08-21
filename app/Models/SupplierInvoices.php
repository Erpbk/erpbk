<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SupplierInvoices extends BaseModel
{
    use LogsActivity, SoftDeletes;

  public $table = 'supplier_invoices';

  public $fillable = [
    'inv_date',
    'supplier_id',
    'garage_id',
    'month_invoice',
    'descriptions',
    'total_amount',
    'billing_month',
    'notes',
    'inv_id',
    'subtotal',
    'vat',
    'partial_paid_amount',
    'status',
    'is_order',
    'is_invoice',
    'attachment',
    'created_by',
    'updated_by',
    'deleted_by',
    'order_date'
  ];

  protected $casts = [
    'garage_id' => 'integer',
    'partial_paid_amount' => 'array',
    'descriptions' => 'string',
    'total_amount' => 'float',
    'notes' => 'string',
    'billing_month' => 'date',
    'inv_date' => 'date',
    'order_date' => 'date',
  ];

  public static array $rules = [
    'inv_date' => 'nullable',
    'supplier_id' => 'required',
    'garage_id' => 'nullable|exists:garages,id',
    'month_invoice' => 'nullable',
    'descriptions' => 'nullable|string|max:65535',
    'total_amount' => 'nullable|numeric',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'billing_month' => 'nullable',
    'notes' => 'nullable|string|max:500',
  ];
  
  
  
  protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $lastNumber = self::max('id') ?? 0;

            $invoice->inv_id = 'SUP' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        });
    }

  public function supplier()
  {
    return $this->belongsTo(Supplier::class);
  }

  public function garage()
  {
    return $this->belongsTo(Garages::class, 'garage_id');
  }

  public function items()
  {
      return $this->hasMany(SupplierInvoicesItem::class, 'inv_id');
  }

  public function inventoryPurchases()
  {
      return $this->hasMany(InventoryPurchase::class, 'inv_id')->orderBy('id');
  }

  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  public function getInvoiceNumberAttribute()
  {
    return $this->inv_id;
  }

  public function getPaidAmountAttribute()
  {
    return array_sum($this->partial_paid_amount ?? []);
  }

  public function getBalanceAttribute()
  {
    return $this->total_amount - $this->paid_amount;
  }

  public static function getIdFromInvoiceNumber($invoiceNumber)
  {
    // Remove the prefix 'CI-' and get the numeric part
    $numericPart = str_replace('SUP', '', $invoiceNumber);
    
    // Remove leading zeros and convert to integer
    $id = (int) ltrim($numericPart, '0');
    
    // Verify the invoice exists
    return self::where('id', $id)->exists() ? $id : null;
  }

  public function usedInventoryBlocksDeletion(): bool
  {
    return InventoryPurchase::hasBeenUsedForInvoice($this->id)
      && ! InventoryPurchase::canReassignUsageForInvoice($this->id);
  }

  /**
   * After the invoice is actually soft-deleted (approval or bypass), reassign
   * used stock and soft-delete related inventory + SUP ledger rows.
   */
  public function finalizeSoftDeletion(?int $deletedBy = null): void
  {
    $wasBypassing = \App\Services\DeleteRequestService::isBypassing();
    \App\Services\DeleteRequestService::bypass(true);

    try {
      InventoryPurchase::reassignUsageForInvoice($this->id);

      if (InventoryPurchase::hasBeenUsedForInvoice($this->id)) {
        if (method_exists($this, 'trashed') && $this->trashed()) {
          $this->restore();
          if (Schema::hasColumn($this->getTable(), 'deleted_by') && $this->deleted_by) {
            $this->deleted_by = null;
            $this->save();
          }
        }

        throw new \RuntimeException('Cannot delete Invoice. Inventory from this Purchase has already been used and cannot be moved to other stock in the same garage.');
      }

      if ($deletedBy && Schema::hasColumn($this->getTable(), 'deleted_by')) {
        $this->deleted_by = $deletedBy;
        $this->save();
      }

      foreach (InventoryPurchase::where('inv_id', $this->id)->get() as $purchase) {
        $purchase->delete();
      }

      $transactions = Transactions::withoutGlobalScope('branch')
        ->where('reference_type', 'SUP')
        ->where('reference_id', $this->id)
        ->get();

      foreach ($transactions as $transaction) {
        if ($deletedBy && Schema::hasColumn($transaction->getTable(), 'deleted_by')) {
          $transaction->deleted_by = $deletedBy;
          $transaction->save();
        }
        $transaction->delete();
      }
    } finally {
      if (! $wasBypassing) {
        \App\Services\DeleteRequestService::bypass(false);
      }
    }
  }

  /**
   * @return array<int, string>
   */
  public function restoreRelatedRecords(): array
  {
    $restored = [];

    foreach (InventoryPurchase::onlyTrashed()->where('inv_id', $this->id)->get() as $purchase) {
      $purchase->restore();
      $restored[] = 'Inventory ' . ($purchase->batch_no ?: '#' . $purchase->id);
    }

    $transactions = Transactions::onlyTrashed()
      ->withoutGlobalScope('branch')
      ->where('reference_type', 'SUP')
      ->where('reference_id', $this->id)
      ->get();

    foreach ($transactions as $transaction) {
      $transaction->restore();
      if (Schema::hasColumn($transaction->getTable(), 'deleted_by') && $transaction->deleted_by) {
        $transaction->deleted_by = null;
        $transaction->save();
      }
      $restored[] = 'Transaction #' . $transaction->id;
    }

    if (Schema::hasColumn($this->getTable(), 'deleted_by') && $this->deleted_by) {
      $this->deleted_by = null;
      $this->save();
    }

    return $restored;
  }

  /**
   * Permanently remove related inventory, ledger rows, line items, and attachment.
   *
   * @return array<int, string>
   */
  public function purgeRelatedRecords(): array
  {
    $deleted = [];

    foreach (InventoryPurchase::withTrashed()->where('inv_id', $this->id)->get() as $purchase) {
      $deleted[] = 'Inventory ' . ($purchase->batch_no ?: '#' . $purchase->id);
      $purchase->forceDelete();
    }

    $transactions = Transactions::withTrashed()
      ->withoutGlobalScope('branch')
      ->where('reference_type', 'SUP')
      ->where('reference_id', $this->id)
      ->get();

    foreach ($transactions as $transaction) {
      $deleted[] = 'Transaction #' . $transaction->id;
      $transaction->forceDelete();
    }

    $this->items()->delete();

    if ($this->attachment && Storage::disk('public')->exists($this->attachment)) {
      Storage::disk('public')->delete($this->attachment);
    }

    return $deleted;
  }
}
