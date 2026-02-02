<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use App\Models\GarageItem;
use App\Models\Supplier;

class TransactionService
{
  /**
   * Record a transaction in the transactions table
   *
   * @param array $data Transaction data
   * @param bool $throwOnError If true, rethrow exception on failure so caller can show the real error
   * @return int|bool ID of created transaction or false on failure
   */
  public function recordTransaction(array $data, $throwOnError = false)
  {
    try {
      $row = [
        'account_id' => $data['account_id'],
        'reference_id' => $data['reference_id'] ?? null,
        'reference_type' => $data['reference_type'] ?? null,
        'trans_code' => $data['trans_code'],
        'trans_date' => $data['trans_date'],
        'narration' => $data['narration'] ?? '',
        'debit' => $data['debit'] ?? 0,
        'credit' => $data['credit'] ?? 0,
        'billing_month' => $data['billing_month'] ?? date('Y-m-01'),
        'created_at' => now(),
        'updated_at' => now(),
      ];
      if (Schema::hasColumn('transactions', 'deleted_at')) {
        $row['deleted_at'] = null;
      }
      $id = DB::table('transactions')->insertGetId($row);

      Log::info('Transaction recorded successfully: ' . $id);
      return $id;
    } catch (\Exception $e) {
      Log::error('Error recording transaction: ' . $e->getMessage());
      if ($throwOnError) {
        throw $e;
      }
      return false;
    }
  }

  /**
   * Delete transaction records using a given identifier.
   *
   * @param  mixed       $identifier     Single identifier or array of identifiers.
   * @param  string      $column         Column to match against (defaults to trans_code).
   * @param  string|null $referenceType  Optional reference type filter when deleting by reference.
   * @return bool                        True when at least one row is deleted, false otherwise.
   */
  public function deleteTransaction($identifier, string $column = 'trans_code', ?string $referenceType = null)
  {
    $allowedColumns = ['id', 'trans_code', 'reference_id', 'account_id'];

    if (!in_array($column, $allowedColumns, true)) {
      $column = 'trans_code';
    }

    $identifiers = is_array($identifier) ? $identifier : [$identifier];
    $identifiers = array_values(array_filter($identifiers, function ($value) {
      return !is_null($value) && $value !== '';
    }));

    if (empty($identifiers)) {
      Log::warning('deleteTransaction called with empty identifier set.');
      return false;
    }

    try {
      $query = DB::table('transactions')->whereIn($column, $identifiers);

      if ($column === 'reference_id' && $referenceType !== null) {
        $query->where('reference_type', $referenceType);
      }

      $deleted = $query->delete();

      Log::info('Transactions deleted', [
        'column' => $column,
        'identifiers' => $identifiers,
        'reference_type' => $referenceType,
        'deleted_rows' => $deleted,
      ]);

      return $deleted > 0;
    } catch (\Exception $e) {
      Log::error('Error deleting transactions: ' . $e->getMessage(), [
        'column' => $column,
        'identifiers' => $identifiers,
        'reference_type' => $referenceType,
      ]);

      return false;
    }
  }

  /**
   * Generate a unique transaction code
   *
   * @param string $prefix Prefix for the transaction code
   * @return string
   */
  public function generateTransCode($prefix = 'GV')
  {
    // Generate a simple numeric transaction code without any prefix
    return mt_rand(1000000, 9999999);
  }

  /**
   * Update transactions for a voucher
   * 
   * @param string|int $transCode
   * @param GarageItem $garageItem
   * @param float $amount
   * @return bool
   */
  public function updateTransactionsForVoucher($transCode, $garageItem, $amount)
  {
    try {
      Log::debug('Updating transactions for trans_code: ' . $transCode);

      // Get supplier information
      $supplier = Supplier::find($garageItem->supplier_id);
      if (!$supplier) {
        throw new \Exception('Supplier not found');
      }

      // Get garage items account ID
      $garageItemsAccountId = Config::get('accounts.garage_items_account_id', 2182);

      // Get supplier account ID
      $supplierAccountId = DB::table('accounts')
        ->where('ref_id', $supplier->id)
        ->where('ref_name', 'Supplier')
        ->value('id');

      if (!$supplierAccountId) {
        $supplierAccountId = Config::get('accounts.default_supplier_account', 1287);
      }

      // Set billing month and transaction date
      $billingMonth = date('Y-m-01');
      $transDate = $garageItem->purchase_date;
      $narration = 'Purchase of garage item: ' . $garageItem->name . ' (Qty: ' . $garageItem->qty . ') from Supplier: ' . $supplier->name;

      // Update debit transaction (garage items account)
      $debitUpdated = DB::table('transactions')
        ->where('trans_code', $transCode)
        ->where('account_id', $garageItemsAccountId)
        ->update([
          'trans_date' => $transDate,
          'narration' => $narration,
          'debit' => $amount,
          'billing_month' => $billingMonth,
          'updated_at' => now()
        ]);

      // Update credit transaction (supplier account)
      $creditUpdated = DB::table('transactions')
        ->where('trans_code', $transCode)
        ->where('account_id', $supplierAccountId)
        ->update([
          'trans_date' => $transDate,
          'narration' => $narration,
          'credit' => $amount,
          'billing_month' => $billingMonth,
          'updated_at' => now()
        ]);

      // Update ledger entries
      $this->updateLedger($garageItemsAccountId, $billingMonth, $amount, 1);
      $this->updateLedger($supplierAccountId, $billingMonth, $amount, 0);

      Log::debug('Updated transactions for trans_code: ' . $transCode . ' - Debit updated: ' . $debitUpdated . ', Credit updated: ' . $creditUpdated);

      return true;
    } catch (\Exception $e) {
      Log::error('Error updating transactions for voucher: ' . $e->getMessage());
      return false;
    }
  }

  public function updateLedger($accountId, $billingMonth, $amount, $type = 1)
  {
    try {
      // Get the last ledger entry for this account
      $lastLedger = DB::table('ledger_entries')
        ->where('account_id', $accountId)
        ->orderBy('billing_month', 'desc')
        ->first();

      $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;
      $debitBalance = $creditBalance = 0.00;

      if ($type === 1) { // Debit
        $debitBalance = $amount;
        $closingBalance = $openingBalance + $amount;
      } else { // Credit
        $creditBalance = $amount;
        $closingBalance = $openingBalance - $amount;
      }

      DB::table('ledger_entries')->insert([
        'account_id' => $accountId,
        'billing_month' => $billingMonth,
        'opening_balance' => $openingBalance,
        'debit_balance' => $debitBalance,
        'credit_balance' => $creditBalance,
        'closing_balance' => $closingBalance,
        'created_at' => now(),
        'updated_at' => now(),
      ]);

      return true;
    } catch (\Exception $e) {
      Log::error('Error updating ledger: ' . $e->getMessage());
      return false;
    }
  }
}
