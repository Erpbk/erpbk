<?php

namespace App\Services;

use App\Helpers\Account;
use App\Models\FuelCardHistory;
use App\Models\FuelCards;
use App\Models\Riders;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Support\GlobalAccounts;
use Carbon\Carbon;

/**
 * Charges a rider for a fuel card that was lost or never returned.
 *
 * Mirrors RiderInventoryLossService: the charge is posted as an Inventory Loss
 * (IL) voucher, debiting the rider and crediting the inventory loss account.
 * Unlike a penalty (PN), this represents company property that is gone.
 */
class FuelCardLossService
{
    /**
     * @return array{voucher: Vouchers, trans_code: string, amount: float, rider: Riders}
     *
     * @throws \RuntimeException
     */
    public function chargeRiderForLostCard(
        FuelCards $card,
        float $amount,
        string $lossDate,
        string $billingMonth,
        ?string $remarks = null,
        ?int $lostBy = null
    ): array {
        if ($card->isLost()) {
            throw new \RuntimeException('This fuel card is already marked as lost.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \RuntimeException('Charge amount must be greater than zero.');
        }

        $rider = $card->chargeableRider();
        if (!$rider) {
            throw new \RuntimeException('No rider has held this card, so there is nobody to charge.');
        }

        if (empty($rider->account_id)) {
            throw new \RuntimeException('Rider account is not configured. Cannot post the fuel card loss charge.');
        }

        $lossAccountId = GlobalAccounts::id('INVENTORY_LOSS');
        if (empty($lossAccountId)) {
            throw new \RuntimeException('Inventory Loss account is not configured in Global Accounts.');
        }

        $transDate = Carbon::parse($lossDate)->format('Y-m-d');
        $billingMonth = Carbon::parse($billingMonth)->startOfMonth()->format('Y-m-d');
        $transCode = Account::trans_code();
        $remarkText = trim((string) ($remarks ?? ''));
        $lostById = $lostBy ?? auth()->id();
        $cardLabel = 'Fuel card ' . $card->card_number;

        $voucher = Vouchers::create([
            'branch_id' => $rider->branch_id ?? $card->branch_id,
            'trans_date' => $transDate,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'IL',
            'remarks' => $remarkText !== '' ? $remarkText : 'Inventory Loss — ' . $cardLabel,
            'amount' => $amount,
            'ref_id' => $card->id,
            'Created_By' => $lostById,
            'status' => 1,
        ]);

        Transactions::create([
            'account_id' => $rider->account_id,
            'reference_id' => $card->id,
            'reference_type' => 'IL',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $this->narration($cardLabel, $remarkText),
            'debit' => $amount,
            'billing_month' => $billingMonth,
            'Created_By' => $lostById,
            'branch_id' => $rider->branch_id ?? $card->branch_id,
        ]);

        Transactions::create([
            'account_id' => $lossAccountId,
            'reference_id' => $card->id,
            'reference_type' => 'IL',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $this->creditNarration($cardLabel, $rider, $remarkText),
            'credit' => $amount,
            'billing_month' => $billingMonth,
            'Created_By' => $lostById,
            'branch_id' => $rider->branch_id ?? $card->branch_id,
        ]);

        $this->closeOpenAssignment($card, $rider->id, $transDate, $lostById, $remarkText);

        $card->status = FuelCards::STATUS_LOST;
        $card->assigned_to = null;
        $card->lost_date = $transDate;
        $card->lost_rider_id = $rider->id;
        $card->lost_amount = $amount;
        $card->lost_voucher_id = $voucher->id;
        $card->lost_trans_code = $transCode;
        $card->lost_remarks = $remarkText !== '' ? $remarkText : null;
        $card->lost_by = $lostById;
        $card->updated_by = $lostById;
        $card->save();

        return [
            'voucher' => $voucher,
            'trans_code' => $transCode,
            'amount' => $amount,
            'rider' => $rider,
        ];
    }

    /**
     * The card is gone, so the open assignment row is closed out on the loss date.
     * This frees the rider to receive another card.
     */
    private function closeOpenAssignment(
        FuelCards $card,
        int $riderId,
        string $transDate,
        ?int $lostById,
        string $remarkText
    ): void {
        $openHistory = FuelCardHistory::where('card_id', $card->id)
            ->where('assigned_to', $riderId)
            ->whereNull('return_date')
            ->orderByDesc('id')
            ->first();

        if (!$openHistory) {
            return;
        }

        $note = 'Card lost / not returned';
        if ($remarkText !== '') {
            $note .= ' — ' . $remarkText;
        }

        $openHistory->return_date = $transDate;
        $openHistory->returned_by = $lostById;
        $openHistory->note = trim((string) $openHistory->note) !== ''
            ? $openHistory->note . ' | ' . $note
            : $note;
        $openHistory->save();
    }

    private function narration(string $cardLabel, string $remarkText): string
    {
        $narration = 'Fuel card loss: ' . $cardLabel;

        return $remarkText !== '' ? $narration . ' (' . $remarkText . ')' : $narration;
    }

    private function creditNarration(string $cardLabel, Riders $rider, string $remarkText): string
    {
        $narration = 'Fuel card loss: ' . $cardLabel . ' — '
            . ($rider->name ?? 'Rider') . ' (' . ($rider->rider_id ?? $rider->id) . ')';

        return $remarkText !== '' ? $narration . ' (' . $remarkText . ')' : $narration;
    }
}
