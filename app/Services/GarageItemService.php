<?php

namespace App\Services;

use App\Models\GarageItem;
use App\Models\Vouchers;
use App\Models\Supplier;
use App\Models\Account;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class GarageItemService
{
    /**
     * Calculate weighted average price when adding more of the same item
     *
     * @param GarageItem $existingItem
     * @param int $newQty
     * @param float $newPrice
     * @return array
     */
    public function calculateWeightedAverage(GarageItem $existingItem, int $newQty, float $newPrice): array
    {
        // Calculate total value of existing items
        $existingValue = $existingItem->qty * $existingItem->avg_price;

        // Calculate value of new items
        $newValue = $newQty * $newPrice;

        // Calculate total quantity after addition
        $totalQty = $existingItem->qty + $newQty;

        // Calculate weighted average price
        $weightedAvgPrice = ($existingValue + $newValue) / $totalQty;

        // Calculate total amount
        $totalAmount = $totalQty * $weightedAvgPrice;

        return [
            'qty' => $totalQty,
            'avg_price' => $weightedAvgPrice,
            'price' => $newPrice, // Keep the latest price
            'total_amount' => $totalAmount
        ];
    }

    /**
     * Find existing vouchers for a garage item
     *
     * @param int $garageItemId
     * @return Collection
     */
    public function findGarageItemVouchers(int $garageItemId): Collection
    {
        Log::debug('Finding vouchers for garage item ID: ' . $garageItemId);

        $vouchers = DB::table('vouchers')
            ->where('ref_id', $garageItemId)
            ->where('voucher_type', 'GV')
            ->get();

        Log::debug('Found ' . $vouchers->count() . ' vouchers for garage item ID: ' . $garageItemId);

        return $vouchers;
    }

    /**
     * Update an existing garage voucher
     *
     * @param int $voucherId
     * @param GarageItem $garageItem
     * @param float $amount
     * @return object|null
     */
    public function updateGarageVoucher(int $voucherId, GarageItem $garageItem, float $amount)
    {
        try {
            Log::debug('Updating voucher ID: ' . $voucherId . ' for garage item: ' . $garageItem->name);

            $supplier = Supplier::find($garageItem->supplier_id);
            if (!$supplier) {
                throw new \Exception('Supplier not found');
            }

            // Update voucher data
            $voucherData = [
                'vendor_id'     => $garageItem->supplier_id,
                'trans_date'    => $garageItem->purchase_date,
                'reason'        => 'Garage Item: ' . $garageItem->name,
                'remarks'       => 'Purchase of garage item: ' . $garageItem->name . ' (Qty: ' . $garageItem->qty . ') from Supplier: ' . $supplier->name,
                'amount'        => $amount,
                'Updated_By'    => Auth::id() ?: 1,
                'updated_at'    => now(),
            ];

            Log::debug('Updating voucher with data: ' . json_encode($voucherData));

            // Update the voucher
            $updated = DB::table('vouchers')
                ->where('id', $voucherId)
                ->update($voucherData);

            if ($updated) {
                Log::info('Voucher updated successfully: ' . $voucherId);

                // Get the updated voucher
                $voucher = DB::table('vouchers')->where('id', $voucherId)->first();

                // Update associated transactions
                $transactionService = new TransactionService();
                $transactionService->updateTransactionsForVoucher($voucher->trans_code, $garageItem, $amount);

                return $voucher;
            } else {
                Log::error('Failed to update voucher: ' . $voucherId);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error updating garage voucher: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a garage voucher (GV) for item addition
     *
     * @param GarageItem $garageItem
     * @param int $qty
     * @param float $price
     * @return object
     */
    public function createGarageVoucher(GarageItem $garageItem, int $qty, float $price): object
    {
        try {
            // Add debug logs
            Log::debug('Starting createGarageVoucher method');
            Log::debug('Garage Item: ' . json_encode($garageItem->toArray()));
            Log::debug('Qty: ' . $qty . ', Price: ' . $price);

            DB::beginTransaction();

            // Get supplier information
            $supplier = Supplier::find($garageItem->supplier_id);
            Log::debug('Supplier found: ' . ($supplier ? 'Yes, ID: ' . $supplier->id : 'No'));

            if (!$supplier) {
                throw new \Exception('Supplier not found');
            }

            // Initialize Transaction Service
            $transactionService = new TransactionService();

            // Generate a unique transaction code
            $transCode = Account::trans_code();

            // Set billing month and transaction date
            $billingMonth = date('Y-m-01');
            $transDate = $garageItem->purchase_date;
            $totalAmount = $price * $qty;

            Log::info('Creating GV voucher for Garage Item: ' . $garageItem->name . ' from Supplier: ' . $supplier->name);

            // 1. Debit Garage Items Account
            $garageItemsAccountId = Config::get('accounts.garage_items_account_id', 2182);

            $transactionService->recordTransaction([
                'account_id'     => $garageItemsAccountId,
                'reference_id'   => $garageItem->id,
                'reference_type' => 'GARAGE',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => 'Purchase of garage item: ' . $garageItem->name . ' (Qty: ' . $qty . ')',
                'debit'          => $totalAmount,
                'billing_month'  => $billingMonth,
            ]);

            // 2. Credit Supplier Account
            $supplierAccountId = DB::table('accounts')
                ->where('ref_id', $supplier->id)
                ->where('ref_name', 'Supplier')
                ->value('id');

            Log::debug('Supplier account lookup - ref_id: ' . $supplier->id . ', result: ' . ($supplierAccountId ?: 'Not found'));

            // Check if accounts table exists and has records
            $accountsTableExists = DB::getSchemaBuilder()->hasTable('accounts');
            Log::debug('Accounts table exists: ' . ($accountsTableExists ? 'Yes' : 'No'));

            if ($accountsTableExists) {
                $accountsCount = DB::table('accounts')->count();
                Log::debug('Accounts table record count: ' . $accountsCount);

                // Sample some accounts for debugging
                $sampleAccounts = DB::table('accounts')->limit(3)->get();
                Log::debug('Sample accounts: ' . json_encode($sampleAccounts));
            }

            if (!$supplierAccountId) {
                // If supplier account not found, use a default account
                $supplierAccountId = Config::get('accounts.default_supplier_account', 1287);
                Log::warning("Supplier account not found for supplier ID: {$supplier->id}. Using default account: {$supplierAccountId}");
            }

            $transactionService->recordTransaction([
                'account_id'     => $supplierAccountId,
                'reference_id'   => $garageItem->id,
                'reference_type' => 'GARAGE',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => 'Purchase of garage item: ' . $garageItem->name . ' (Qty: ' . $qty . ') from Supplier: ' . $supplier->name,
                'credit'         => $totalAmount,
                'billing_month'  => $billingMonth,
            ]);

            // 3. Create Voucher
            $voucherData = [
                'vendor_id'     => $garageItem->supplier_id, // Use vendor_id instead of supplier_id
                'trans_date'    => $transDate,
                'trans_code'    => $transCode,
                'billing_month' => $billingMonth,
                'voucher_type'  => 'GV',
                'reason'        => 'Garage Item: ' . $garageItem->name,
                'remarks'       => 'Purchase of garage item: ' . $garageItem->name . ' (Qty: ' . $qty . ') from Supplier: ' . $supplier->name,
                'amount'        => $totalAmount,
                'Created_By'    => Auth::id() ?: 1, // Default to admin user if Auth::id() is null
                'ref_id'        => $garageItem->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            Log::debug('Voucher data: ' . json_encode($voucherData));
            Log::debug('Auth::id(): ' . (Auth::id() ?: 'null'));

            // Check if vouchers table exists
            $vouchersTableExists = DB::getSchemaBuilder()->hasTable('vouchers');
            Log::debug('Vouchers table exists: ' . ($vouchersTableExists ? 'Yes' : 'No'));

            if ($vouchersTableExists) {
                // Get vouchers table structure
                $vouchersColumns = DB::getSchemaBuilder()->getColumnListing('vouchers');
                Log::debug('Vouchers table columns: ' . json_encode($vouchersColumns));
            }

            try {
                $voucherId = DB::table('vouchers')->insertGetId($voucherData);
                Log::debug('Voucher created with ID: ' . $voucherId);
                $voucher = DB::table('vouchers')->where('id', $voucherId)->first();
            } catch (\Exception $e) {
                Log::error('Error creating voucher: ' . $e->getMessage());
                throw $e;
            }

            // 4. Update Ledger Entries
            // For Garage Items Account (Debit)
            $transactionService->updateLedger($garageItemsAccountId, $billingMonth, $totalAmount, 1);

            // For Supplier Account (Credit)
            $transactionService->updateLedger($supplierAccountId, $billingMonth, $totalAmount, 0);

            DB::commit();

            Log::info('Created GV voucher #' . $voucher->id . ' with transaction code: ' . $transCode);
            Log::info('Debit Account: ' . $garageItemsAccountId . ' (Garage Items), Amount: ' . $totalAmount);
            Log::info('Credit Account: ' . $supplierAccountId . ' (Supplier: ' . $supplier->name . '), Amount: ' . $totalAmount);

            return $voucher;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating garage voucher: ' . $e->getMessage());
            return (object)['error' => $e->getMessage()];
        }
    }
}
