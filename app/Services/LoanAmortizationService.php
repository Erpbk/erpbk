<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\Carbon;

class LoanAmortizationService
{
    /**
     * Build monthly installment schedule rows (not persisted).
     *
     * @return list<array<string, mixed>>
     */
    public function buildSchedule(
        float $principal,
        float $annualRatePercent,
        int $termMonths,
        Carbon $firstPaymentDate,
        string $interestCalculationMethod = Loan::INTEREST_REDUCING
    ): array {
        if ($termMonths < 1 || $principal <= 0) {
            return [];
        }

        return $interestCalculationMethod === Loan::INTEREST_FLAT
            ? $this->buildFlatSchedule($principal, $annualRatePercent, $termMonths, $firstPaymentDate)
            : $this->buildReducingBalanceSchedule($principal, $annualRatePercent, $termMonths, $firstPaymentDate);
    }

    /**
     * Flat rate: interest computed on original principal for full term; equal principal + equal interest each month.
     *
     * @return list<array<string, mixed>>
     */
    protected function buildFlatSchedule(
        float $principal,
        float $annualRatePercent,
        int $termMonths,
        Carbon $firstPaymentDate
    ): array {
        $totalInterest = round($principal * ($annualRatePercent / 100) * ($termMonths / 12), 2);
        $monthlyInterest = round($totalInterest / $termMonths, 2);
        $monthlyPrincipal = round($principal / $termMonths, 2);
        $emi = round($monthlyPrincipal + $monthlyInterest, 2);

        $schedule = [];
        $balance = round($principal, 2);
        $interestRunning = 0.0;
        $principalRunning = 0.0;

        for ($i = 1; $i <= $termMonths; $i++) {
            $interest = $monthlyInterest;
            $principalPart = $monthlyPrincipal;

            if ($i === $termMonths) {
                $interest = round($totalInterest - $interestRunning, 2);
                $principalPart = round($principal - $principalRunning, 2);
                $emi = round($principalPart + $interest, 2);
            }

            $closing = round($balance - $principalPart, 2);
            if ($closing < 0) {
                $closing = 0;
            }

            $schedule[] = [
                'installment_no' => $i,
                'due_date' => $firstPaymentDate->copy()->addMonths($i - 1)->format('Y-m-d'),
                'opening_balance' => $balance,
                'principal_amount' => $principalPart,
                'interest_amount' => $interest,
                'total_amount' => $emi,
            ];

            $balance = $closing;
            $interestRunning += $interest;
            $principalRunning += $principalPart;
        }

        return $this->adjustScheduleRounding($schedule, $principal);
    }

    /**
     * Reducing balance: EMI on declining outstanding principal.
     *
     * @return list<array<string, mixed>>
     */
    protected function buildReducingBalanceSchedule(
        float $principal,
        float $annualRatePercent,
        int $termMonths,
        Carbon $firstPaymentDate
    ): array {
        $monthlyRate = ($annualRatePercent / 100) / 12;
        $emi = $this->calculateEmi($principal, $monthlyRate, $termMonths);

        $schedule = [];
        $balance = round($principal, 2);

        for ($i = 1; $i <= $termMonths; $i++) {
            $interest = $monthlyRate > 0
                ? round($balance * $monthlyRate, 2)
                : 0.0;

            $principalPart = round($emi - $interest, 2);

            if ($i === $termMonths) {
                $principalPart = round($balance, 2);
                $emi = round($principalPart + $interest, 2);
            }

            $closing = round($balance - $principalPart, 2);
            if ($closing < 0) {
                $closing = 0;
            }

            $schedule[] = [
                'installment_no' => $i,
                'due_date' => $firstPaymentDate->copy()->addMonths($i - 1)->format('Y-m-d'),
                'opening_balance' => $balance,
                'principal_amount' => $principalPart,
                'interest_amount' => $interest,
                'total_amount' => $emi,
            ];

            $balance = $closing;
        }

        return $this->adjustScheduleRounding($schedule, $principal);
    }

    public function calculateEmi(float $principal, float $monthlyRate, int $termMonths): float
    {
        if ($termMonths <= 0) {
            return 0.0;
        }

        if ($monthlyRate <= 0) {
            return round($principal / $termMonths, 2);
        }

        $factor = pow(1 + $monthlyRate, $termMonths);
        $emi = $principal * $monthlyRate * $factor / ($factor - 1);

        return round($emi, 2);
    }

    /**
     * @param  list<array<string, mixed>>  $schedule
     * @return list<array<string, mixed>>
     */
    protected function adjustScheduleRounding(array $schedule, float $principal): array
    {
        if ($schedule === []) {
            return $schedule;
        }

        $sumPrincipal = round(array_sum(array_column($schedule, 'principal_amount')), 2);
        $diff = round($principal - $sumPrincipal, 2);

        if (abs($diff) > 0 && abs($diff) <= 0.05) {
            $last = count($schedule) - 1;
            $schedule[$last]['principal_amount'] = round($schedule[$last]['principal_amount'] + $diff, 2);
            $schedule[$last]['total_amount'] = round(
                $schedule[$last]['principal_amount'] + $schedule[$last]['interest_amount'],
                2
            );
        }

        return $schedule;
    }

    /**
     * Replace installment rows for a loan.
     */
    public function persistSchedule(Loan $loan, array $schedule): void
    {
        LoanInstallment::where('loan_id', $loan->id)->forceDelete();

        foreach ($schedule as $row) {
            LoanInstallment::create([
                'loan_id' => $loan->id,
                'installment_no' => $row['installment_no'],
                'due_date' => $row['due_date'],
                'opening_balance' => $row['opening_balance'],
                'principal_amount' => $row['principal_amount'],
                'interest_amount' => $row['interest_amount'],
                'total_amount' => $row['total_amount'],
                'status' => LoanInstallment::STATUS_PENDING,
            ]);
        }

        $lastDue = end($schedule);
        $loan->maturity_date = $lastDue ? $lastDue['due_date'] : null;
        $loan->emi_amount = $schedule[0]['total_amount'] ?? null;
        $loan->save();
    }
}
