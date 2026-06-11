<?php

namespace App\Services;

use App\Helpers\Account;
use App\Helpers\HeadAccount;
use App\Models\RiderInventoryAssignment;
use App\Models\Transactions;
use App\Models\VoucherType;
use App\Models\Vouchers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RiderInventoryLossService
{
    /**
     * @return array{voucher: \App\Models\Vouchers, trans_code: string, amount: float}
     *
     * @throws \RuntimeException
     */
    public function chargeRiderForLostItem(
        RiderInventoryAssignment $assignment,
        string $lossDate,
        ?string $remarks = null,
        ?int $lostBy = null,
        ?string $returnContractNumber = null
    ): array {
        $assignment->loadMissing(['rider', 'inventoryItem']);
        $rider = $assignment->rider;

        if (!$rider || empty($rider->account_id)) {
            throw new \RuntimeException('Rider account is not configured. Cannot post inventory loss charge.');
        }

        $amount = (float) $assignment->amount;
        if ($amount <= 0) {
            $amount = (float) ($assignment->inventoryItem->item_price ?? 0);
        }

        if ($amount <= 0) {
            throw new \RuntimeException('Inventory item price must be greater than zero to mark as lost.');
        }

        $transDate = Carbon::parse($lossDate)->format('Y-m-d');
        $billingMonth = date('Y-m-01', strtotime($transDate));
        $transCode = Account::trans_code();
        $itemName = $assignment->inventoryItem->name ?? 'Inventory Item';
        $narration = 'Inventory loss: ' . $itemName . ' — ' . ($rider->name ?? 'Rider');
        $remarkText = trim((string) ($remarks ?? ''));

        if ($remarkText !== '') {
            $narration .= ' (' . $remarkText . ')';
        }

        $voucher = Vouchers::create([
            'branch_id' => $rider->branch_id,
            'trans_date' => $transDate,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'IL',
            'remarks' => $remarkText !== '' ? $remarkText : 'Inventory Loss — ' . $itemName,
            'amount' => $amount,
            'ref_id' => $assignment->id,
            'Created_By' => $lostBy ?? auth()->id(),
            'status' => 1,
        ]);

        Transactions::create([
            'account_id' => $rider->account_id,
            'reference_id' => $assignment->id,
            'reference_type' => 'IL',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'debit' => $amount,
            'billing_month' => $billingMonth,
            'Created_By' => $lostBy ?? auth()->id(),
            'branch_id' => $rider->branch_id,
        ]);

        Transactions::create([
            'account_id' => HeadAccount::INVENTORY_LOSS,
            'reference_id' => $assignment->id,
            'reference_type' => 'IL',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'credit' => $amount,
            'billing_month' => $billingMonth,
            'Created_By' => $lostBy ?? auth()->id(),
            'branch_id' => $rider->branch_id,
        ]);

        $assignment->status = RiderInventoryAssignment::STATUS_LOST;
        $assignment->loss_date = $transDate;
        $assignment->lost_by = $lostBy ?? auth()->id();
        $assignment->returned_by = $lostBy ?? auth()->id();
        $assignment->return_date = $transDate;
        $assignment->remarks = $remarkText !== '' ? $remarkText : $assignment->remarks;
        $assignment->trans_code = $transCode;
        $assignment->il_voucher_number = $transCode;
        $assignment->voucher_id = $voucher->id;
        $assignment->amount = $amount;
        $assignment->updated_by = $lostBy ?? auth()->id();

        if ($returnContractNumber) {
            $assignment->return_contract_number = $returnContractNumber;
        }

        $assignment->save();

        return [
            'voucher' => $voucher,
            'trans_code' => $transCode,
            'amount' => $amount,
        ];
    }
}
