<?php

namespace App\Services\FixedAssets;

use App\Models\AssetDepreciationSchedule;
use App\Models\FixedAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixedAssetDepreciationPostingService
{
    public function __construct(
        private readonly FixedAssetVoucherService $voucherService,
        private readonly DepreciationScheduleService $scheduleService
    ) {
    }

    public function postDueEntries(?string $asOfDate = null): int
    {
        $asOf = $asOfDate ?? now()->toDateString();
        $postedCount = 0;

        $schedules = AssetDepreciationSchedule::query()
            ->where('status', AssetDepreciationSchedule::STATUS_PENDING)
            ->where('depreciation_amount', '>', 0)
            ->whereDate('period_date', '<=', $asOf)
            ->whereHas('fixedAsset', function ($query) {
                $query->where('status', FixedAsset::STATUS_ACTIVE);
            })
            ->with('fixedAsset')
            ->orderBy('fixed_asset_id')
            ->orderBy('period_number')
            ->get();

        foreach ($schedules->groupBy('fixed_asset_id') as $assetSchedules) {
            $asset = $assetSchedules->first()?->fixedAsset;
            if (!$asset || !$asset->canPostDepreciation()) {
                continue;
            }

            $assetPosted = 0;

            foreach ($assetSchedules as $schedule) {
                try {
                    DB::beginTransaction();

                    $voucher = $this->voucherService->createDepreciationVoucher($asset, $schedule);

                    $schedule->update([
                        'status' => AssetDepreciationSchedule::STATUS_POSTED,
                        'voucher_id' => $voucher->id,
                    ]);

                    DB::commit();
                    $assetPosted++;
                    $postedCount++;
                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::error('Failed to post fixed asset depreciation', [
                        'schedule_id' => $schedule->id,
                        'fixed_asset_id' => $schedule->fixed_asset_id,
                        'error' => $e->getMessage(),
                    ]);
                    break;
                }
            }

            if ($assetPosted > 0) {
                $asset->refresh();
                $this->scheduleService->regenerate($asset);
            }
        }

        return $postedCount;
    }

    public function postDueForAsset(FixedAsset $asset, ?string $asOfDate = null): int
    {
        if (!$asset->canPostDepreciation()) {
            return 0;
        }

        $asOf = $asOfDate ?? now()->toDateString();
        $postedCount = 0;

        $schedules = AssetDepreciationSchedule::query()
            ->where('fixed_asset_id', $asset->id)
            ->where('status', AssetDepreciationSchedule::STATUS_PENDING)
            ->where('depreciation_amount', '>', 0)
            ->whereDate('period_date', '<=', $asOf)
            ->orderBy('period_number')
            ->get();

        foreach ($schedules as $schedule) {
            try {
                DB::beginTransaction();

                $voucher = $this->voucherService->createDepreciationVoucher($asset, $schedule);

                $schedule->update([
                    'status' => AssetDepreciationSchedule::STATUS_POSTED,
                    'voucher_id' => $voucher->id,
                ]);

                DB::commit();
                $postedCount++;
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }

        if ($postedCount > 0) {
            $asset->refresh();
            $this->scheduleService->regenerate($asset);
        }

        return $postedCount;
    }
}
