<?php

namespace App\Services;

use App\Helpers\Account;
use App\Models\Employee;
use App\Models\Riders;
use App\Models\SimHistory;
use App\Models\Sims;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Support\GlobalAccounts;
use Carbon\Carbon;

/**
 * Charges a rider or employee for a SIM that was lost or never returned.
 *
 * Mirrors FuelCardLossService: the charge is posted as an Inventory Loss (IL)
 * voucher, debiting the holder and crediting the inventory loss account.
 */
class SimLossService
{
    /**
     * @return array{voucher: Vouchers, trans_code: string, amount: float, person: Riders|Employee, person_type: string}
     *
     * @throws \RuntimeException
     */
    public function chargePersonForLostSim(
        Sims $sim,
        float $amount,
        string $lossDate,
        string $billingMonth,
        ?string $remarks = null,
        ?int $lostBy = null
    ): array {
        if ($sim->isLost()) {
            throw new \RuntimeException('This SIM is already marked as lost.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \RuntimeException('Charge amount must be greater than zero.');
        }

        $chargeable = $sim->chargeablePerson();
        if (! $chargeable) {
            throw new \RuntimeException('No rider or employee has held this SIM, so there is nobody to charge.');
        }

        /** @var Riders|Employee $person */
        $person = $chargeable['model'];
        $personType = $chargeable['type'];

        if (empty($person->account_id)) {
            $label = $personType === 'employee' ? 'Employee' : 'Rider';
            throw new \RuntimeException("{$label} account is not configured. Cannot post the SIM loss charge.");
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
        $simLabel = 'SIM ' . $sim->number;
        $personName = $person->name ?? ($personType === 'employee' ? 'Employee' : 'Rider');
        $personCode = $personType === 'employee'
            ? ($person->employee_id ?? $person->id)
            : ($person->rider_id ?? $person->id);

        $voucher = Vouchers::create([
            'branch_id' => $person->branch_id ?? $sim->branch_id,
            'trans_date' => $transDate,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'IL',
            'remarks' => $remarkText !== '' ? $remarkText : 'Inventory Loss — ' . $simLabel,
            'amount' => $amount,
            'ref_id' => $sim->id,
            'Created_By' => $lostById,
            'status' => 1,
        ]);

        Transactions::create([
            'account_id' => $person->account_id,
            'reference_id' => $sim->id,
            'reference_type' => 'IL',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $this->narration($simLabel, $remarkText),
            'debit' => $amount,
            'billing_month' => $billingMonth,
            'Created_By' => $lostById,
            'branch_id' => $person->branch_id ?? $sim->branch_id,
        ]);

        Transactions::create([
            'account_id' => $lossAccountId,
            'reference_id' => $sim->id,
            'reference_type' => 'IL',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $this->creditNarration($simLabel, $personName, (string) $personCode, $remarkText),
            'credit' => $amount,
            'billing_month' => $billingMonth,
            'Created_By' => $lostById,
            'branch_id' => $person->branch_id ?? $sim->branch_id,
        ]);

        $this->closeOpenAssignment($sim, $personType, (int) $person->id, $transDate, $lostById, $remarkText);

        $sim->status = Sims::STATUS_LOST;
        $sim->assign_to = null;
        $sim->assign_type = null;
        $sim->lost_date = $transDate;
        $sim->lost_rider_id = $personType === 'rider' ? (int) $person->id : null;
        $sim->lost_employee_id = $personType === 'employee' ? (int) $person->id : null;
        $sim->lost_amount = $amount;
        $sim->lost_voucher_id = $voucher->id;
        $sim->lost_trans_code = $transCode;
        $sim->lost_remarks = $remarkText !== '' ? $remarkText : null;
        $sim->lost_by = $lostById;
        $sim->updated_by = $lostById;
        $sim->save();

        return [
            'voucher' => $voucher,
            'trans_code' => $transCode,
            'amount' => $amount,
            'person' => $person,
            'person_type' => $personType,
        ];
    }

    private function closeOpenAssignment(
        Sims $sim,
        string $personType,
        int $personId,
        string $transDate,
        ?int $lostById,
        string $remarkText
    ): void {
        $query = SimHistory::where('sim_id', $sim->id)->whereNull('return_date');
        if ($personType === 'employee') {
            $query->where('employee_id', $personId);
        } else {
            $query->where('rider_id', $personId);
        }

        $openHistory = $query->orderByDesc('id')->first();
        if (! $openHistory) {
            return;
        }

        $note = 'SIM lost / not returned';
        if ($remarkText !== '') {
            $note .= ' — ' . $remarkText;
        }

        $openHistory->return_date = $transDate;
        $openHistory->returned_by = $lostById;
        $openHistory->notes = trim((string) $openHistory->notes) !== ''
            ? $openHistory->notes . ' | ' . $note
            : $note;
        $openHistory->save();
    }

    private function narration(string $simLabel, string $remarkText): string
    {
        $narration = 'SIM loss: ' . $simLabel;

        return $remarkText !== '' ? $narration . ' (' . $remarkText . ')' : $narration;
    }

    private function creditNarration(string $simLabel, string $personName, string $personCode, string $remarkText): string
    {
        $narration = 'SIM loss: ' . $simLabel . ' — ' . $personName . ' (' . $personCode . ')';

        return $remarkText !== '' ? $narration . ' (' . $remarkText . ')' : $narration;
    }
}
