<?php

namespace App\Services;

use App\Helpers\Account;
use App\Helpers\HeadAccount;
use App\Models\RiderInventoryAssignment;
use App\Models\Transactions;
use App\Models\Vouchers;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
        $result = $this->chargeRiderForLostItems(
            collect([$assignment]),
            $lossDate,
            $remarks,
            $lostBy,
            $returnContractNumber
        );

        return [
            'voucher' => $result['voucher'],
            'trans_code' => $result['trans_code'],
            'amount' => $result['total_amount'],
        ];
    }

    /**
     * @param  Collection<int, RiderInventoryAssignment>|array<int, RiderInventoryAssignment>  $assignments
     * @return array{voucher: \App\Models\Vouchers, trans_code: string, total_amount: float}
     *
     * @throws \RuntimeException
     */
    public function chargeRiderForLostItems(
        Collection|array $assignments,
        string $lossDate,
        ?string $remarks = null,
        ?int $lostBy = null,
        ?string $returnContractNumber = null
    ): array {
        $assignments = $assignments instanceof Collection ? $assignments->values() : collect($assignments)->values();

        if ($assignments->isEmpty()) {
            throw new \RuntimeException('No inventory items provided for loss charge.');
        }

        $assignments->each(fn (RiderInventoryAssignment $assignment) => $assignment->loadMissing(['rider', 'inventoryItem']));

        $rider = $assignments->first()->rider;
        if (!$rider || empty($rider->account_id)) {
            throw new \RuntimeException('Rider account is not configured. Cannot post inventory loss charge.');
        }

        $lineItems = [];
        $totalAmount = 0.0;

        foreach ($assignments as $assignment) {
            $unitPrice = (float) $assignment->amount;
            if ($unitPrice <= 0) {
                $unitPrice = (float) ($assignment->inventoryItem->price ?? 0);
            }

            $qty = max(1, (int) ($assignment->qty ?? 1));
            $amount = round($unitPrice * $qty, 2);

            if ($amount <= 0) {
                $itemName = $assignment->inventoryItem->name ?? 'Inventory Item';
                throw new \RuntimeException('Inventory item price must be greater than zero to mark as lost: ' . $itemName);
            }

            $lineItems[] = [
                'assignment' => $assignment,
                'amount' => $amount,
                'item_name' => $assignment->inventoryItem->name ?? 'Inventory Item',
            ];
            $totalAmount += $amount;
        }

        $transDate = Carbon::parse($lossDate)->format('Y-m-d');
        $billingMonth = date('Y-m-01', strtotime($transDate));
        $transCode = Account::trans_code();
        $remarkText = trim((string) ($remarks ?? ''));
        $lostById = $lostBy ?? auth()->id();
        $isBulk = count($lineItems) > 1;

        $itemNames = collect($lineItems)->pluck('item_name')->implode(', ');
        $voucherRemarks = $remarkText !== ''
            ? $remarkText
            : 'Inventory Loss — ' . $itemNames;

        $voucher = Vouchers::create([
            'branch_id' => $rider->branch_id,
            'trans_date' => $transDate,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'IL',
            'remarks' => $voucherRemarks,
            'amount' => $totalAmount,
            'ref_id' => $assignments->first()->id,
            'Created_By' => $lostById,
            'status' => 1,
        ]);

        foreach ($lineItems as $lineItem) {
            /** @var RiderInventoryAssignment $assignment */
            $assignment = $lineItem['assignment'];
            $amount = $lineItem['amount'];
            $narration = $this->buildLossNarration($lineItem['item_name'], $remarkText);

            Transactions::create([
                'account_id' => $rider->account_id,
                'reference_id' => $assignment->id,
                'reference_type' => 'IL',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $narration,
                'debit' => $amount,
                'billing_month' => $billingMonth,
                'Created_By' => $lostById,
                'branch_id' => $rider->branch_id,
            ]);

            if (!$isBulk) {
                $creditNarration = $this->buildCreditLossNarration($lineItem['item_name'], $rider, $remarkText);

                Transactions::create([
                    'account_id' => HeadAccount::INVENTORY_LOSS,
                    'reference_id' => $assignment->id,
                    'reference_type' => 'IL',
                    'trans_code' => $transCode,
                    'trans_date' => $transDate,
                    'narration' => $creditNarration,
                    'credit' => $amount,
                    'billing_month' => $billingMonth,
                    'Created_By' => $lostById,
                    'branch_id' => $rider->branch_id,
                ]);
            }

            $assignment->status = RiderInventoryAssignment::STATUS_LOST;
            $assignment->loss_date = $transDate;
            $assignment->lost_by = $lostById;
            $assignment->returned_by = $lostById;
            $assignment->return_date = $transDate;
            $assignment->remarks = $remarkText !== '' ? $remarkText : $assignment->remarks;
            $assignment->trans_code = $transCode;
            $assignment->il_voucher_number = $transCode;
            $assignment->voucher_id = $voucher->id;
            $assignment->amount = $amount;
            $assignment->updated_by = $lostById;

            if ($returnContractNumber) {
                $assignment->return_contract_number = $returnContractNumber;
            }

            $assignment->save();
        }

        if ($isBulk) {
            $creditNarration = $this->buildCreditLossNarration($itemNames, $rider, $remarkText);

            Transactions::create([
                'account_id' => HeadAccount::INVENTORY_LOSS,
                'reference_id' => $voucher->id,
                'reference_type' => 'IL',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $creditNarration,
                'credit' => $totalAmount,
                'billing_month' => $billingMonth,
                'Created_By' => $lostById,
                'branch_id' => $rider->branch_id,
            ]);
        }

        return [
            'voucher' => $voucher,
            'trans_code' => $transCode,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Remove loss charge for one assignment. For batch vouchers, only removes that item's
     * transactions and adjusts the consolidated credit unless only two lines remain.
     *
     * @throws \RuntimeException
     */
    public function reverseLossChargeForAssignment(RiderInventoryAssignment $assignment): void
    {
        $transCode = $assignment->trans_code;

        if (empty($transCode) && !empty($assignment->voucher_id)) {
            $voucher = Vouchers::find($assignment->voucher_id);
            $transCode = $voucher?->trans_code;
        }

        if (empty($transCode)) {
            Transactions::where('reference_type', 'IL')
                ->where('reference_id', $assignment->id)
                ->delete();

            if (!empty($assignment->voucher_id)) {
                Vouchers::where('id', $assignment->voucher_id)->delete();
            }

            $this->clearAssignmentLossFields($assignment);

            return;
        }

        $transactionCount = Transactions::where('trans_code', $transCode)->count();

        if ($transactionCount <= 2) {
            Transactions::where('trans_code', $transCode)->delete();
            Vouchers::where('trans_code', $transCode)->delete();
        } else {
            Transactions::where('trans_code', $transCode)
                ->where('reference_id', $assignment->id)
                ->where('reference_type', 'IL')
                ->delete();

            $remainingDebitTotal = (float) Transactions::where('trans_code', $transCode)
                ->where('debit', '>', 0)
                ->sum('debit');

            $creditTransactions = Transactions::where('trans_code', $transCode)
                ->where('account_id', HeadAccount::INVENTORY_LOSS)
                ->where('credit', '>', 0)
                ->get();

            if ($creditTransactions->count() === 1) {
                $creditTransactions->first()->credit = $remainingDebitTotal;
                $creditTransactions->first()->save();
            }

            Vouchers::where('trans_code', $transCode)->update(['amount' => $remainingDebitTotal]);
        }

        $this->clearAssignmentLossFields($assignment);
    }

    private function clearAssignmentLossFields(RiderInventoryAssignment $assignment): void
    {
        $assignment->loss_date = null;
        $assignment->lost_by = null;
        $assignment->trans_code = null;
        $assignment->il_voucher_number = null;
        $assignment->voucher_id = null;
        $assignment->return_date = null;
        $assignment->returned_by = null;
        $assignment->updated_by = auth()->id();
        $assignment->save();
    }

    private function buildLossNarration(string $itemName, string $remarkText = ''): string
    {
        $narration = 'Inventory loss: ' . $itemName;

        if ($remarkText !== '') {
            $narration .= ' (' . $remarkText . ')';
        }

        return $narration;
    }

    private function buildCreditLossNarration(string $itemName, object $rider, string $remarkText = ''): string
    {
        $riderId = $rider->rider_id ?? $rider->id;
        $riderName = $rider->name ?? 'Rider';
        $narration = 'Inventory loss: ' . $itemName . ' — ' . $riderName . ' (' . $riderId . ')';

        if ($remarkText !== '') {
            $narration .= ' (' . $remarkText . ')';
        }

        return $narration;
    }
}
