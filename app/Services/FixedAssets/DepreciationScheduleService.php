<?php

namespace App\Services\FixedAssets;

use App\Models\AssetCategory;
use App\Models\AssetDepreciationSchedule;
use App\Models\FixedAsset;
use Carbon\Carbon;

class DepreciationScheduleService
{
    public function regenerate(FixedAsset $asset): void
    {
        $asset->refresh();

        $postedSchedules = AssetDepreciationSchedule::query()
            ->where('fixed_asset_id', $asset->id)
            ->where('status', AssetDepreciationSchedule::STATUS_POSTED)
            ->orderBy('period_number')
            ->get();

        AssetDepreciationSchedule::query()
            ->where('fixed_asset_id', $asset->id)
            ->whereIn('status', [
                AssetDepreciationSchedule::STATUS_PENDING,
                AssetDepreciationSchedule::STATUS_SKIPPED,
            ])
            ->delete();

        $totalPeriods = $this->totalPeriods($asset);
        $remainingDepreciable = $this->remainingDepreciableForSchedule($asset, $postedSchedules);

        if ($remainingDepreciable <= 0 || $totalPeriods <= 0) {
            if ($asset->status !== FixedAsset::STATUS_DISPOSED && $asset->status !== FixedAsset::STATUS_DRAFT) {
                $asset->update(['status' => FixedAsset::STATUS_FULLY_DEPRECIATED]);
            }

            return;
        }

        $inServiceDate = Carbon::parse($asset->scheduleStartDate());
        $openingAccumulated = (float) $asset->opening_accumulated_depreciation;
        $postedFromSchedule = (float) $postedSchedules->sum('depreciation_amount');
        $accumulated = $openingAccumulated + $postedFromSchedule;

        $rows = [];
        $elapsedPeriods = 0;
        $firstSchedulePeriod = max($postedSchedules->count() + 1, 1);
        $handling = $asset->isOpeningBalance() ? null : $asset->past_depreciation_handling;

        if ($asset->isOpeningBalance()) {
            $elapsedPeriods = $this->elapsedPeriodsBeforeAsOf($asset);
            $firstSchedulePeriod = max($postedSchedules->count() + 1, $elapsedPeriods + 1);
        } elseif ($handling === FixedAsset::PAST_DEPR_CURRENT_PERIOD) {
            $elapsedPeriods = $this->elapsedPeriodsThroughDate($asset, FixedAsset::endOfLastMonthDate());
            $firstSchedulePeriod = max($postedSchedules->count() + 1, $elapsedPeriods + 1);
        } elseif ($handling === FixedAsset::PAST_DEPR_CATCH_UP) {
            $elapsedPeriods = $this->elapsedPeriodsThroughDate($asset, FixedAsset::endOfLastMonthDate());
            $catchUpAmount = 0.0;

            if ($postedSchedules->isEmpty() && $elapsedPeriods > 0) {
                $catchUpAmount = min(
                    $this->calculateDepreciationThroughPeriod($asset, $elapsedPeriods),
                    $remainingDepreciable
                );
            }

            if ($catchUpAmount > 0) {
                $accumulated += $catchUpAmount;
                $remainingDepreciable -= $catchUpAmount;

                $rows[] = $this->buildScheduleRow(
                    $asset,
                    max(1, $elapsedPeriods),
                    FixedAsset::endOfLastMonthDate()->toDateString(),
                    $catchUpAmount,
                    $accumulated
                );
            }

            $firstSchedulePeriod = max($postedSchedules->count() + 1, $elapsedPeriods + 1);
        }

        for ($period = $firstSchedulePeriod; $period <= $totalPeriods; $period++) {
            if ($remainingDepreciable <= 0) {
                break;
            }

            $periodDate = $this->periodDate($inServiceDate, $period, $asset->depreciation_frequency);
            $remainingPeriods = $totalPeriods - $period + 1;
            $isLastPeriod = $period === $totalPeriods;

            $amount = $this->calculatePeriodAmount(
                $asset,
                $accumulated,
                $remainingDepreciable,
                $remainingPeriods,
                $isLastPeriod
            );

            $amount = round(min($amount, $remainingDepreciable), 2);

            if ($amount <= 0) {
                break;
            }

            $accumulated += $amount;
            $remainingDepreciable -= $amount;

            $rows[] = $this->buildScheduleRow(
                $asset,
                $period,
                $periodDate->toDateString(),
                $amount,
                $accumulated
            );
        }

        if ($asset->isOpeningBalance() && $elapsedPeriods > 0 && $postedSchedules->isEmpty()) {
            array_unshift($rows, ...$this->historicalSkippedRows($asset, $inServiceDate, $elapsedPeriods, $openingAccumulated));
        } elseif ($handling === FixedAsset::PAST_DEPR_CURRENT_PERIOD && $elapsedPeriods > 0 && $postedSchedules->isEmpty()) {
            array_unshift($rows, ...$this->historicalSkippedRows($asset, $inServiceDate, $elapsedPeriods, 0));
        }

        if (!empty($rows)) {
            usort($rows, fn ($a, $b) => $a['period_number'] <=> $b['period_number']);
            AssetDepreciationSchedule::insert($rows);
        }

        if ($asset->status !== FixedAsset::STATUS_DISPOSED) {
            $newStatus = $asset->status === FixedAsset::STATUS_DRAFT
                ? FixedAsset::STATUS_DRAFT
                : $this->resolveAssetStatus($asset);
            $asset->update(['status' => $newStatus]);
        }
    }

    private function buildScheduleRow(
        FixedAsset $asset,
        int $periodNumber,
        string $periodDate,
        float $amount,
        float $accumulated
    ): array {
        $bookValue = max((float) $asset->salvage_value, (float) $asset->acquisition_cost - $accumulated);

        return [
            'company_id' => $asset->company_id,
            'fixed_asset_id' => $asset->id,
            'period_number' => $periodNumber,
            'period_date' => $periodDate,
            'depreciation_amount' => round($amount, 2),
            'accumulated_depreciation' => round($accumulated, 2),
            'book_value' => round($bookValue, 2),
            'status' => AssetDepreciationSchedule::STATUS_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function resolveAssetStatus(FixedAsset $asset): string
    {
        $totalTarget = $asset->totalDepreciableAmount();
        $recognizedAccumulated = (float) $asset->opening_accumulated_depreciation
            + (float) AssetDepreciationSchedule::query()
                ->where('fixed_asset_id', $asset->id)
                ->where('status', AssetDepreciationSchedule::STATUS_POSTED)
                ->sum('depreciation_amount');

        $hasPendingDepreciation = AssetDepreciationSchedule::query()
            ->where('fixed_asset_id', $asset->id)
            ->where('status', AssetDepreciationSchedule::STATUS_PENDING)
            ->where('depreciation_amount', '>', 0)
            ->exists();

        if ($hasPendingDepreciation) {
            return FixedAsset::STATUS_ACTIVE;
        }

        if ($recognizedAccumulated >= $totalTarget - 0.01) {
            return FixedAsset::STATUS_FULLY_DEPRECIATED;
        }

        return FixedAsset::STATUS_ACTIVE;
    }

    public function applyCategoryDefaults(FixedAsset $asset, AssetCategory $category, float $acquisitionCost): void
    {
        $asset->depreciation_method = $category->depreciation_method;
        $asset->depreciation_frequency = $category->depreciation_frequency;
        $asset->useful_life_months = $category->useful_life_months;
        $asset->salvage_value = $category->salvageValueForCost($acquisitionCost);
        $asset->asset_account_id = $category->asset_account_id;
        $asset->accumulated_depreciation_account_id = $category->accumulated_depreciation_account_id;
        $asset->depreciation_expense_account_id = $category->depreciation_expense_account_id;
    }

    public function totalPeriods(FixedAsset $asset): int
    {
        if ($asset->depreciation_frequency === AssetCategory::FREQUENCY_YEARLY) {
            return max(1, (int) ceil($asset->useful_life_months / 12));
        }

        return max(1, (int) $asset->useful_life_months);
    }

    public function elapsedPeriodsBeforeAsOf(FixedAsset $asset): int
    {
        if (!$asset->isOpeningBalance()) {
            return 0;
        }

        return $this->elapsedPeriodsThroughDate($asset, Carbon::parse($asset->depreciationAsOfDate()));
    }

    public function elapsedPeriodsThroughDate(FixedAsset $asset, Carbon $throughDate): int
    {
        $inService = Carbon::parse($asset->scheduleStartDate())->startOfDay();
        $through = $throughDate->copy()->startOfDay();

        if ($through->lt($inService)) {
            return 0;
        }

        $totalPeriods = $this->totalPeriods($asset);
        $elapsed = 0;

        for ($period = 1; $period <= $totalPeriods; $period++) {
            $periodDate = $this->periodDate($inService, $period, $asset->depreciation_frequency);
            if ($periodDate->startOfDay()->lte($through)) {
                $elapsed = $period;
            } else {
                break;
            }
        }

        return $elapsed;
    }

    private function calculateDepreciationThroughPeriod(FixedAsset $asset, int $throughPeriod): float
    {
        if ($throughPeriod <= 0) {
            return 0.0;
        }

        $totalPeriods = $this->totalPeriods($asset);
        $remainingDepreciable = $asset->remainingDepreciableAmount();
        $accumulated = (float) $asset->opening_accumulated_depreciation;
        $total = 0.0;

        for ($period = 1; $period <= min($throughPeriod, $totalPeriods); $period++) {
            $remainingPeriods = $totalPeriods - $period + 1;
            $isLastPeriod = $period === $totalPeriods;

            $amount = $this->calculatePeriodAmount(
                $asset,
                $accumulated,
                $remainingDepreciable,
                $remainingPeriods,
                $isLastPeriod
            );

            $amount = round(min($amount, $remainingDepreciable), 2);

            if ($amount <= 0) {
                break;
            }

            $total += $amount;
            $accumulated += $amount;
            $remainingDepreciable -= $amount;
        }

        return round($total, 2);
    }

    private function remainingDepreciableForSchedule(FixedAsset $asset, $postedSchedules): float
    {
        $postedAmount = (float) $postedSchedules->sum('depreciation_amount');

        return max(0, $asset->remainingDepreciableAmount() - $postedAmount);
    }

    private function historicalSkippedRows(
        FixedAsset $asset,
        Carbon $inServiceDate,
        int $elapsedPeriods,
        float $openingAccumulated
    ): array {
        $rows = [];

        for ($period = 1; $period <= $elapsedPeriods; $period++) {
            $rows[] = [
                'company_id' => $asset->company_id,
                'fixed_asset_id' => $asset->id,
                'period_number' => $period,
                'period_date' => $this->periodDate($inServiceDate, $period, $asset->depreciation_frequency)->toDateString(),
                'depreciation_amount' => 0,
                'accumulated_depreciation' => round($openingAccumulated, 2),
                'book_value' => round(max((float) $asset->salvage_value, (float) $asset->acquisition_cost - $openingAccumulated), 2),
                'status' => AssetDepreciationSchedule::STATUS_SKIPPED,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $rows;
    }

    private function periodDate(Carbon $inServiceDate, int $periodNumber, string $frequency): Carbon
    {
        if ($frequency === AssetCategory::FREQUENCY_YEARLY) {
            return $inServiceDate->copy()->addYears($periodNumber)->endOfMonth();
        }

        return $inServiceDate->copy()->addMonths($periodNumber)->endOfMonth();
    }

    private function calculatePeriodAmount(
        FixedAsset $asset,
        float $accumulatedBefore,
        float $remainingDepreciable,
        int $remainingPeriods,
        bool $isLastPeriod
    ): float {
        $salvageValue = (float) $asset->salvage_value;
        $bookValue = (float) $asset->acquisition_cost - $accumulatedBefore;
        $maxDepreciation = $bookValue - $salvageValue;

        if ($maxDepreciation <= 0) {
            return 0;
        }

        if ($isLastPeriod) {
            return min($remainingDepreciable, $maxDepreciation);
        }

        if ($asset->depreciation_method === AssetCategory::METHOD_STRAIGHT_LINE) {
            return min($remainingDepreciable / $remainingPeriods, $maxDepreciation);
        }

        $decliningAmount = $bookValue * $this->periodDepreciationRate($asset);
        $straightLineFloor = $remainingDepreciable / $remainingPeriods;

        return min(max($decliningAmount, $straightLineFloor), $maxDepreciation);
    }

    private function periodDepreciationRate(FixedAsset $asset): float
    {
        $usefulLifeYears = max($asset->useful_life_months / 12, 1 / 12);
        $multiplier = $asset->depreciation_method === AssetCategory::METHOD_DOUBLE_DECLINING_BALANCE ? 2 : 1;
        $annualRate = $multiplier / $usefulLifeYears;

        if ($asset->depreciation_frequency === AssetCategory::FREQUENCY_YEARLY) {
            return $annualRate;
        }

        return $annualRate / 12;
    }
}
